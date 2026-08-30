<?php

namespace Tests\Feature\Documents;

use App\Contracts\MalwareScanner;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\DocumentReviewStatus;
use App\Exceptions\FileSecurityException;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Services\ApplicationTransitionService;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_document_is_scanned_stored_private_and_versioned(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();

        $document = app(DocumentWorkflowService::class)->upload(
            $setup['application'],
            $setup['requirement'],
            $this->validPdf(),
            $setup['user'],
        );

        Storage::disk('private')->assertExists($document->storage_path);
        Storage::disk('quarantine')->assertMissing($document->storage_path);
        $this->assertSame(1, $document->version_number);
        $this->assertSame(DocumentReviewStatus::PENDING, $document->review_status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'document.uploaded', 'auditable_id' => $document->id]);

        $replacement = app(DocumentWorkflowService::class)->upload(
            $setup['application']->fresh(),
            $setup['requirement']->fresh(),
            $this->validPdf('ktp-v2.pdf'),
            $setup['user'],
        );

        $this->assertSame(2, $replacement->version_number);
        $this->assertFalse($document->fresh()->active);
        $this->assertTrue($replacement->fresh()->active);
        Storage::disk('private')->assertExists($replacement->storage_path);
    }

    public function test_dangerous_extension_signature_mismatch_and_oversized_files_are_rejected(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $service = app(DocumentWorkflowService::class);

        $this->expectException(FileSecurityException::class);
        $service->upload($setup['application'], $setup['requirement'], UploadedFile::fake()->createWithContent('payload.php', '<?php echo "bad";'), $setup['user']);
    }

    public function test_signature_mismatch_is_rejected_even_when_extension_is_allowed(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();

        $this->expectException(FileSecurityException::class);
        app(DocumentWorkflowService::class)->upload(
            $setup['application'],
            $setup['requirement'],
            UploadedFile::fake()->createWithContent('fake.pdf', '<?php not a pdf;'),
            $setup['user'],
        );
    }

    public function test_pdf_active_content_is_rejected(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();

        $this->expectException(FileSecurityException::class);
        app(DocumentWorkflowService::class)->upload(
            $setup['application'],
            $setup['requirement'],
            UploadedFile::fake()->createWithContent('active.pdf', "%PDF-1.4\n/JavaScript (bad)\n%%EOF"),
            $setup['user'],
        );
    }

    public function test_unavailable_malware_scanner_blocks_upload_and_cleans_quarantine(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $this->app->instance(MalwareScanner::class, new class implements MalwareScanner
        {
            public function scan(string $absolutePath): array
            {
                return ['clean' => false, 'available' => false];
            }
        });

        try {
            app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);
            $this->fail('Expected unavailable malware scanner to block the upload.');
        } catch (FileSecurityException $exception) {
            $this->assertSame('Pemeriksaan keamanan file belum tersedia.', $exception->getMessage());
        }
        Storage::disk('quarantine')->assertDirectoryEmpty('applications');
    }

    public function test_infected_file_is_rejected_and_removed_from_quarantine(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $this->app->instance(MalwareScanner::class, new class implements MalwareScanner
        {
            public function scan(string $absolutePath): array
            {
                return ['clean' => false, 'available' => true];
            }
        });

        try {
            app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);
            $this->fail('Expected infected file to be rejected.');
        } catch (FileSecurityException $exception) {
            $this->assertSame('File ditolak oleh pemeriksaan keamanan.', $exception->getMessage());
        }
        Storage::disk('quarantine')->assertDirectoryEmpty('applications');
    }

    public function test_requirement_code_cannot_escape_private_application_directory(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $setup['requirement']->forceFill(['code' => '../../outside'])->save();

        $document = app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);

        $this->assertStringNotContainsString('..', $document->storage_path);
        $this->assertStringStartsWith('applications/'.$setup['application']->public_id.'/documents/', $document->storage_path);
    }

    public function test_oversized_document_is_rejected_before_storage(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();

        $this->expectException(FileSecurityException::class);
        app(DocumentWorkflowService::class)->upload(
            $setup['application'],
            $setup['requirement'],
            UploadedFile::fake()->create('large.pdf', 6 * 1024, 'application/pdf'),
            $setup['user'],
        );
        Storage::disk('private')->assertDirectoryEmpty('applications');
    }

    public function test_requirement_specific_size_limit_is_enforced(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $sixMegabytePdf = "%PDF-1.4\n".str_repeat('A', 6 * 1024 * 1024).'\n%%EOF';

        $setup['requirement']->forceFill(['max_size_bytes' => 10 * 1024 * 1024])->save();
        $document = app(DocumentWorkflowService::class)->upload(
            $setup['application'],
            $setup['requirement']->fresh(),
            UploadedFile::fake()->createWithContent('sk-ahu.pdf', $sixMegabytePdf),
            $setup['user'],
        );
        $this->assertSame(1, $document->version_number);

        $setup['requirement']->forceFill(['max_size_bytes' => 5 * 1024 * 1024])->save();
        try {
            app(DocumentWorkflowService::class)->upload(
                $setup['application']->fresh(),
                $setup['requirement']->fresh(),
                UploadedFile::fake()->createWithContent('sk-ahu-too-large.pdf', $sixMegabytePdf),
                $setup['user'],
            );
            $this->fail('Expected requirement-specific size limit to reject the file.');
        } catch (FileSecurityException $exception) {
            $this->assertSame('Ukuran file melebihi batas.', $exception->getMessage());
        }
    }

    public function test_admin_acceptance_locks_document_and_revision_opens_only_target_requirement(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $document = app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);
        $admin = $this->admin();

        $this->moveToUnderReview($setup['application']);
        app(DocumentWorkflowService::class)->review($document, $admin, DocumentReviewAction::ACCEPT);
        $this->assertSame(DocumentReviewStatus::LOCKED, $document->fresh()->review_status);
        $this->assertSame('ACCEPTED', $setup['requirement']->fresh()->status);

        $this->expectException(\DomainException::class);
        app(DocumentWorkflowService::class)->upload($setup['application']->fresh(), $setup['requirement']->fresh(), $this->validPdf('locked.pdf'), $setup['user']);
    }

    public function test_revision_request_allows_new_version_and_preserves_old_version(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $document = app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);
        $admin = $this->admin();
        $this->moveToUnderReview($setup['application']);

        app(DocumentWorkflowService::class)->review($document, $admin, DocumentReviewAction::REQUEST_REVISION, 'Foto buram', 'Unggah foto yang lebih jelas.');
        $this->assertSame(DocumentReviewStatus::REVISION_REQUIRED, $document->fresh()->review_status);
        $this->assertSame('REVISION_REQUIRED', $setup['requirement']->fresh()->status);

        app(ApplicationTransitionService::class)->transition($setup['application']->fresh(), ApplicationStatus::REVISION_REQUIRED, $admin, 'Perbaikan diperlukan');
        $replacement = app(DocumentWorkflowService::class)->upload($setup['application']->fresh(), $setup['requirement']->fresh(), $this->validPdf('ktp-v2.pdf'), $setup['user']);

        $this->assertSame(2, $replacement->version_number);
        $this->assertFalse($document->fresh()->active);
        $this->assertTrue($replacement->fresh()->active);
    }

    public function test_document_download_and_view_are_authorized_and_audited(): void
    {
        Storage::fake('private');
        Storage::fake('quarantine');
        $setup = $this->documentSetup();
        $document = app(DocumentWorkflowService::class)->upload($setup['application'], $setup['requirement'], $this->validPdf(), $setup['user']);

        $this->actingAs($setup['user'])->get(route('client.documents.download', $document->public_id))->assertOk();
        $this->actingAs($setup['user'])->get(route('client.documents.view', $document->public_id))->assertOk();
        $this->assertDatabaseHas('document_access_logs', ['document_id' => $document->id, 'action' => 'DOWNLOAD', 'actor_type' => 'user']);
        $this->assertDatabaseHas('document_access_logs', ['document_id' => $document->id, 'action' => 'VIEW', 'actor_type' => 'user']);

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('client.documents.download', $document->public_id))->assertForbidden();
    }

    public function test_purge_command_removes_expired_physical_file_and_audits_it(): void
    {
        Storage::fake('private');
        $setup = $this->documentSetup();
        $document = Document::factory()->create([
            'application_id' => $setup['application']->id,
            'application_requirement_id' => $setup['requirement']->id,
            'uploaded_by_user_id' => $setup['user']->id,
            'storage_disk' => 'private',
            'storage_path' => 'applications/purge/synthetic.pdf',
            'retention_until' => now()->subMinute(),
            'deleted_at' => null,
        ]);
        Storage::disk('private')->put($document->storage_path, '%PDF-1.4\n%%EOF');

        Artisan::call('files:purge');

        Storage::disk('private')->assertMissing($document->storage_path);
        $this->assertNotNull($document->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'file.purged', 'auditable_id' => $document->id]);
    }

    /** @return array{user:User,application:Application,requirement:ApplicationRequirement} */
    private function documentSetup(): array
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $serviceRequirement = ServiceRequirement::factory()->create([
            'service_id' => $service->id,
            'code' => 'KTP',
            'max_size_bytes' => 5 * 1024 * 1024,
        ]);
        $application = Application::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_DOCUMENTS,
        ]);
        $requirement = ApplicationRequirement::factory()->create([
            'application_id' => $application->id,
            'service_requirement_id' => $serviceRequirement->id,
            'code' => $serviceRequirement->code,
            'name' => $serviceRequirement->name,
            'allowed_extensions' => $serviceRequirement->allowed_extensions,
            'allowed_mimes' => $serviceRequirement->allowed_mimes,
            'max_size_bytes' => $serviceRequirement->max_size_bytes,
        ]);

        return compact('user', 'application', 'requirement');
    }

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
            $current = $transitions->transition($current, $target, null, 'synthetic test');
        }
    }

    private function validPdf(string $name = 'ktp.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }
}
