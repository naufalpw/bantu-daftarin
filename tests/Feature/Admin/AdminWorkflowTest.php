<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\ResultDocumentType;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Notifications\ResultAvailableNotification;
use App\Services\AdminWorkflowService;
use App\Services\ApplicationTransitionService;
use App\Services\DocumentWorkflowService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_estimate_process_upload_verify_and_complete(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication();
        $admin = $this->admin();
        $document = app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf('ktp.pdf'), $setup['client']);
        $this->moveToUnderReview($setup['application']);

        $workflow = app(AdminWorkflowService::class);
        $workflow->reviewDocument($document, $admin, DocumentReviewAction::ACCEPT, null, null);
        $application = $workflow->finalizeReview($setup['application']->fresh(), $admin);
        $this->assertSame(ApplicationStatus::DOCUMENTS_ACCEPTED, $application->status);

        $application = $workflow->setEstimate($application, $admin, now()->addDays(5), 'Kapasitas proses synthetic');
        $this->assertSame(ApplicationStatus::IN_PROGRESS, $application->status);
        $this->assertDatabaseHas('application_estimate_histories', ['application_id' => $application->id, 'admin_id' => $admin->id, 'reason' => 'Kapasitas proses synthetic']);

        $application = $workflow->markWaitingExternal($application, $admin);
        $result = $workflow->uploadResult($application, $admin, $this->validPdf('npwp-result.pdf'), ResultDocumentType::PRIMARY_RESULT);
        $this->assertSame(ApplicationStatus::RESULT_UPLOADED, $application->fresh()->status);
        Storage::disk('private')->assertExists($result->storage_path);

        $application = $workflow->beginResultReview($application->fresh(), $admin);
        $workflow->verifyResult($result->fresh(), $admin, true);
        Notification::assertSentTo($setup['client'], ResultAvailableNotification::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $setup['client']->id,
            'type' => 'result.available',
        ]);
        $application = $workflow->complete($application->fresh(), $admin);

        $this->assertSame(ApplicationStatus::COMPLETED, $application->status);
        $this->assertSame('VERIFIED', $result->fresh()->verification_status->value);
        $this->assertDatabaseHas('audit_logs', ['event' => 'result.uploaded', 'auditable_id' => $result->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'result.verification_changed', 'auditable_id' => $result->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'application.status_changed', 'auditable_id' => $application->id]);
    }

    public function test_result_notification_is_sent_only_after_primary_result_verification_and_is_idempotent(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication(ApplicationStatus::WAITING_EXTERNAL_PROCESS);
        $admin = $this->admin();
        $workflow = app(AdminWorkflowService::class);
        $result = $workflow->uploadResult($setup['application'], $admin, $this->validPdf('pending-result.pdf'), ResultDocumentType::PRIMARY_RESULT);
        $workflow->beginResultReview($setup['application']->fresh(), $admin);

        Notification::assertNotSentTo($setup['client'], ResultAvailableNotification::class);

        $workflow->verifyResult($result->fresh(), $admin, true);
        $workflow->verifyResult($result->fresh(), $admin, true);

        Notification::assertSentTo($setup['client'], ResultAvailableNotification::class);
        $this->assertSame(1, \App\Models\Notification::query()->where('user_id', $setup['client']->id)->where('type', 'result.available')->count());
    }

    public function test_result_notification_mail_uses_authorized_application_link_and_safe_content(): void
    {
        $setup = $this->setupApplication(ApplicationStatus::RESULT_REVIEW);
        $notification = new ResultAvailableNotification(
            (string) $setup['application']->public_id,
            'synthetic-result-public-id',
        );

        $message = $notification->toMail($setup['client']);
        $content = implode(' ', array_map('strval', array_merge($message->introLines, $message->outroLines)));

        $this->assertSame('Hasil layanan tersedia', $message->subject);
        $this->assertSame('Lihat hasil layanan', $message->actionText);
        $this->assertSame(route('client.applications.show', $setup['application']->public_id), $message->actionUrl);
        $this->assertStringContainsString('Hasil layanan Anda telah tersedia.', $content);
        $this->assertStringNotContainsString('3173055501010001', $content);
        $this->assertStringNotContainsString('3173055501010002', $content);
        $this->actingAs($setup['client'])
            ->get($message->actionUrl)
            ->assertOk();
    }

    public function test_result_notification_is_queued_for_the_application_owner(): void
    {
        Queue::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication(ApplicationStatus::WAITING_EXTERNAL_PROCESS);
        $admin = $this->admin();
        $workflow = app(AdminWorkflowService::class);
        $result = $workflow->uploadResult($setup['application'], $admin, $this->validPdf('queued-result.pdf'), ResultDocumentType::PRIMARY_RESULT);
        $workflow->beginResultReview($setup['application']->fresh(), $admin);

        $workflow->verifyResult($result->fresh(), $admin, true);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($setup): bool {
            return $job->notification instanceof ResultAvailableNotification
                && $job->notification->applicationId === (string) $setup['application']->public_id
                && $job->notifiables->contains('id', $setup['client']->id);
        });
    }

    public function test_result_notification_failure_does_not_change_verified_result(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication(ApplicationStatus::WAITING_EXTERNAL_PROCESS);
        $admin = $this->admin();
        $workflow = app(AdminWorkflowService::class);
        $result = $workflow->uploadResult($setup['application'], $admin, $this->validPdf('failure-result.pdf'), ResultDocumentType::PRIMARY_RESULT);
        $workflow->beginResultReview($setup['application']->fresh(), $admin);

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('resultAvailable')->once()->andThrow(new \RuntimeException('synthetic mail failure'));
        $this->app->instance(NotificationService::class, $notifications);

        app(AdminWorkflowService::class)->verifyResult($result->fresh(), $admin, true);

        $this->assertSame('VERIFIED', $result->fresh()->verification_status->value);
    }

    public function test_estimate_requires_accepted_documents(): void
    {
        Notification::fake();
        $setup = $this->setupApplication();
        $admin = $this->admin();

        $this->expectException(\DomainException::class);
        app(AdminWorkflowService::class)->setEstimate($setup['application'], $admin, now()->addDay(), 'Belum siap');
    }

    public function test_completion_requires_verified_primary_result(): void
    {
        Notification::fake();
        $setup = $this->setupApplication(ApplicationStatus::RESULT_REVIEW);
        $admin = $this->admin();

        $this->expectException(\DomainException::class);
        app(AdminWorkflowService::class)->complete($setup['application'], $admin);
    }

    public function test_rejected_result_requires_reason_and_client_can_only_access_verified_result(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication(ApplicationStatus::WAITING_EXTERNAL_PROCESS);
        $admin = $this->admin();
        $workflow = app(AdminWorkflowService::class);
        $result = $workflow->uploadResult($setup['application'], $admin, $this->validPdf('pending-result.pdf'), ResultDocumentType::PRIMARY_RESULT);
        $workflow->beginResultReview($setup['application']->fresh(), $admin);

        try {
            $workflow->verifyResult($result->fresh(), $admin, false);
            $this->fail('Expected a rejection reason validation exception.');
        } catch (\DomainException $exception) {
            $this->assertSame('Berikan alasan jika hasil tidak diverifikasi.', $exception->getMessage());
        }

        $client = $setup['client'];
        $this->actingAs($client)->get(route('client.results.download', $result->public_id))->assertForbidden();
        $workflow->verifyResult($result->fresh(), $admin, false, 'Nomor dokumen tidak terbaca');
        $this->actingAs($client)->get(route('client.results.download', $result->public_id))->assertForbidden();
    }

    public function test_admin_result_upload_rejects_dangerous_content(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->setupApplication(ApplicationStatus::WAITING_EXTERNAL_PROCESS);
        $admin = $this->admin();

        $this->expectException(\DomainException::class);
        app(AdminWorkflowService::class)->uploadResult($setup['application'], $admin, UploadedFile::fake()->createWithContent('bad.pdf', '<?php echo "not pdf";'), ResultDocumentType::PRIMARY_RESULT);
    }

    public function test_client_cannot_trigger_admin_transition_route(): void
    {
        $setup = $this->setupApplication(ApplicationStatus::COMPLETED);

        $this->actingAs($setup['client'])
            ->post(route('admin.applications.archive', $setup['application']->public_id))
            ->assertForbidden();
        $this->assertSame(ApplicationStatus::COMPLETED, $setup['application']->fresh()->status);
    }

    public function test_admin_can_archive_completed_application(): void
    {
        $setup = $this->setupApplication(ApplicationStatus::COMPLETED);
        $admin = $this->admin();

        $this->actingAs($admin->user)
            ->post(route('admin.applications.archive', $setup['application']->public_id))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::ARCHIVED, $setup['application']->fresh()->status);
    }

    public function test_admin_review_route_validates_reason_for_rejected_document(): void
    {
        $setup = $this->setupApplication(ApplicationStatus::UNDER_REVIEW);
        $admin = $this->admin();
        $document = Document::create([
            'application_id' => $setup['application']->id,
            'application_requirement_id' => $setup['requirement']->id,
            'version_number' => 1,
            'original_filename' => 'synthetic.pdf',
            'stored_filename' => 'random.pdf',
            'storage_disk' => 'private',
            'storage_path' => 'applications/synthetic/random.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'sha256_checksum' => hash('sha256', 'synthetic'),
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::PENDING,
            'uploaded_by_user_id' => $setup['client']->id,
            'uploaded_at' => now(),
            'active' => true,
        ]);

        $this->actingAs($admin->user)
            ->post(route('admin.documents.review', $document->public_id), ['action' => DocumentReviewAction::REQUEST_REVISION->value])
            ->assertSessionHasErrors('reason');

        $this->assertSame(DocumentReviewStatus::PENDING, $document->fresh()->review_status);
    }

    /** @return array{client:User,application:Application,requirement:ApplicationRequirement} */
    private function setupApplication(ApplicationStatus $status = ApplicationStatus::AWAITING_DOCUMENTS): array
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $template = ServiceRequirement::factory()->create(['service_id' => $service->id, 'code' => 'KTP']);
        $application = Application::factory()->create(['user_id' => $client->id, 'service_id' => $service->id, 'status' => $status]);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'service_requirement_id' => $template->id,
            'code' => $template->code,
            'name' => $template->name,
            'allowed_extensions' => $template->allowed_extensions,
            'allowed_mimes' => $template->allowed_mimes,
            'max_size_bytes' => $template->max_size_bytes,
        ]);

        return compact('client', 'application', 'requirement');
    }

    /** @return array{user:User,admin:Admin} */
    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => 'SUPER_ADMIN']);

        return Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => 'SUPER_ADMIN', 'is_active' => true]);
    }

    private function moveToUnderReview(Application $application): void
    {
        $transitions = app(ApplicationTransitionService::class);
        $current = $application->fresh();
        foreach ([
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
            ApplicationStatus::AWAITING_PAYMENT,
            ApplicationStatus::PAYMENT_CONFIRMED,
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
        ] as $target) {
            $current = $transitions->transition($current, $target, null, 'synthetic admin test');
        }
    }

    private function validPdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }
}
