<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationCancellationReason;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewStatus;
use App\Enums\DocumentScanStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidApplicationTransition;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\Payment;
use App\Models\PersonalApplicationDetail;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Models\User;
use App\Services\ApplicationCancellationService;
use App\Services\ApplicationTransitionService;
use App\Services\ApplicationWorkflowService;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_pre_payment_state_can_be_cancelled_and_repeated_request_is_idempotent(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $cancellations = app(ApplicationCancellationService::class);

        foreach ([
            ApplicationStatus::DRAFT,
            ApplicationStatus::AWAITING_DOCUMENTS,
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
            ApplicationStatus::AWAITING_PAYMENT,
        ] as $status) {
            $application = $this->application($client, $service, $status);

            $cancelled = $cancellations->cancel($application, $client, ApplicationCancellationReason::WRONG_SERVICE);
            $this->assertSame(ApplicationStatus::CANCELLED, $cancelled->status);
            $this->assertDatabaseHas('application_status_histories', [
                'application_id' => $application->id,
                'from_status' => $status->value,
                'to_status' => ApplicationStatus::CANCELLED->value,
                'actor_type' => 'user',
                'actor_id' => $client->id,
                'reason' => 'Salah memilih layanan',
            ]);

            $cancellations->cancel($application->fresh(), $client, ApplicationCancellationReason::NO_LONGER_CONTINUE);
            $this->assertSame(1, $application->statusHistories()->where('to_status', ApplicationStatus::CANCELLED->value)->count());
        }
    }

    public function test_post_payment_and_terminal_states_cannot_be_cancelled(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create();
        $cancellations = app(ApplicationCancellationService::class);
        $blocked = [
            ApplicationStatus::PAYMENT_CONFIRMED,
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::REVISION_REQUIRED,
            ApplicationStatus::REVISION_SUBMITTED,
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::ESTIMATE_PENDING,
            ApplicationStatus::IN_PROGRESS,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS,
            ApplicationStatus::RESULT_UPLOADED,
            ApplicationStatus::RESULT_REVIEW,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED,
        ];

        foreach ($blocked as $status) {
            $application = $this->application($client, $service, $status);
            $this->assertFalse($cancellations->canBeCancelledByClient($application, $client), $status->value);

            try {
                $cancellations->cancel($application, $client, ApplicationCancellationReason::NO_LONGER_CONTINUE);
                $this->fail("{$status->value} seharusnya tidak dapat dibatalkan.");
            } catch (\DomainException $exception) {
                $this->assertSame('Pengajuan tidak dapat dibatalkan pada tahap ini.', $exception->getMessage());
            }
        }
    }

    public function test_payment_truth_overrides_pre_payment_status_but_unconfirmed_payments_remain_cancellable(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create();
        $cancellations = app(ApplicationCancellationService::class);
        $paidApplication = $this->application($client, $service, ApplicationStatus::AWAITING_PAYMENT);
        $this->payment($paidApplication, PaymentStatus::PAID, ['paid_at' => now()]);

        $this->assertFalse($cancellations->canBeCancelledByClient($paidApplication, $client));
        $this->expectException(\DomainException::class);
        $cancellations->cancel($paidApplication, $client, ApplicationCancellationReason::NO_LONGER_CONTINUE);
    }

    public function test_pending_failed_expired_and_cancelled_provider_payments_are_not_falsified_by_application_cancellation(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create();
        $cancellations = app(ApplicationCancellationService::class);

        foreach ([PaymentStatus::PENDING, PaymentStatus::FAILED, PaymentStatus::EXPIRED, PaymentStatus::CANCELLED] as $status) {
            $application = $this->application($client, $service, ApplicationStatus::AWAITING_PAYMENT);
            $payment = $this->payment($application, $status);

            $this->assertTrue($cancellations->canBeCancelledByClient($application, $client));
            $cancellations->cancel($application, $client, ApplicationCancellationReason::REENTER_DATA);
            $this->assertSame(ApplicationStatus::CANCELLED, $application->fresh()->status);
            $this->assertSame($status, $payment->fresh()->status);
        }
    }

    public function test_cancel_route_requires_ownership_validates_reason_and_preserves_history(): void
    {
        $owner = User::factory()->create();
        $otherClient = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'name' => 'NPWP Perseorangan']);
        $application = $this->application($owner, $service, ApplicationStatus::DRAFT);

        $this->actingAs($owner)
            ->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Batalkan pengajuan')
            ->assertSee('Ya, batalkan pengajuan');

        $this->actingAs($otherClient)
            ->patch(route('client.applications.cancel', $application->public_id), [
                'reason' => ApplicationCancellationReason::WRONG_SERVICE->value,
            ])
            ->assertNotFound();

        $this->actingAs($owner)
            ->from(route('client.applications.show', $application->public_id))
            ->patch(route('client.applications.cancel', $application->public_id), [
                'reason' => ApplicationCancellationReason::OTHER->value,
            ])
            ->assertSessionHasErrors('reason_other');

        $this->actingAs($owner)
            ->patch(route('client.applications.cancel', $application->public_id), [
                'reason' => ApplicationCancellationReason::OTHER->value,
                'reason_other' => 'Perlu memulai ulang dengan data yang benar.',
            ])
            ->assertRedirect(route('client.applications.show', $application->public_id));

        $this->assertSame(ApplicationStatus::CANCELLED, $application->fresh()->status);
        $this->assertDatabaseHas('applications', ['id' => $application->id]);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'to_status' => ApplicationStatus::CANCELLED->value,
            'reason' => 'Lainnya: Perlu memulai ulang dengan data yang benar.',
        ]);
    }

    public function test_generic_transition_cannot_be_used_for_admin_cancellation(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create();
        $application = $this->application($client, $service, ApplicationStatus::DRAFT);
        $admin = $this->admin();

        $this->expectException(InvalidApplicationTransition::class);
        app(ApplicationTransitionService::class)->transition($application, ApplicationStatus::CANCELLED, $admin->user);
    }

    public function test_cancelled_application_is_terminal_read_only_and_can_be_replaced_by_a_new_application(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'name' => 'NPWP Perseorangan']);
        ServiceRequirement::factory()->create(['service_id' => $service->id]);
        $application = $this->application($client, $service, ApplicationStatus::DRAFT);
        $requirement = ApplicationRequirement::factory()->create(['application_id' => $application->id]);
        app(ApplicationCancellationService::class)->cancel($application, $client, ApplicationCancellationReason::START_NEW_APPLICATION);

        $this->assertFalse(app(DocumentWorkflowService::class)->canClientUpload($application->fresh(), $requirement));

        try {
            app(ApplicationTransitionService::class)->transition($application->fresh(), ApplicationStatus::AWAITING_DOCUMENTS, $client);
            $this->fail('Status CANCELLED seharusnya terminal.');
        } catch (InvalidApplicationTransition) {
            $this->assertTrue(true);
        }

        try {
            app(ApplicationWorkflowService::class)->createPayment($application->fresh(), $client, PaymentMethod::BCA);
            $this->fail('Pengajuan yang dibatalkan seharusnya tidak dapat membuat pembayaran.');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $this->actingAs($client)
            ->get(route('client.payments.show', $application->public_id))
            ->assertOk()
            ->assertSee('Pengajuan telah dibatalkan')
            ->assertDontSee('Buat instruksi pembayaran');
        $this->actingAs($client)
            ->post(route('client.payments.store', $application->public_id), ['payment_method' => PaymentMethod::BCA->value])
            ->assertRedirect(route('client.applications.show', $application->public_id))
            ->assertSessionHasErrors('payment');

        $this->actingAs($client)
            ->get(route('npwp.personal'))
            ->assertRedirect(route('client.applications.create', $service->public_id));
        $this->actingAs($client)
            ->get(route('client.services.index'))
            ->assertOk()
            ->assertSee('Lihat persyaratan')
            ->assertDontSee('Pengajuan aktif');
    }

    public function test_cancelled_application_is_historical_for_client_and_admin_and_absent_from_operational_queues(): void
    {
        $client = User::factory()->create(['name' => 'Klien Pembatalan']);
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'name' => 'NPWP Perseorangan']);
        $application = $this->application($client, $service, ApplicationStatus::AWAITING_DOCUMENTS);
        $requirement = ApplicationRequirement::factory()->create(['application_id' => $application->id, 'name' => 'KTP']);
        Document::factory()->create([
            'application_id' => $application->id,
            'application_requirement_id' => $requirement->id,
            'uploaded_by_user_id' => $client->id,
            'scan_status' => DocumentScanStatus::PASSED,
            'review_status' => DocumentReviewStatus::PENDING,
            'storage_path' => 'private/never-public/ktp.jpg',
        ]);
        app(ApplicationCancellationService::class)->cancel($application, $client, ApplicationCancellationReason::START_NEW_APPLICATION);
        $admin = $this->admin();

        $this->actingAs($client)
            ->get(route('client.applications.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertDontSee('Lanjutkan pengajuan');

        $this->actingAs($admin->user)
            ->get(route('admin.applications.index', ['filter' => 'cancelled']))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Klien Pembatalan');
        $this->actingAs($admin->user)
            ->get(route('admin.applications.show', $application->public_id))
            ->assertOk()
            ->assertSee('Pengajuan dibatalkan')
            ->assertSee('Dibatalkan oleh')
            ->assertSee('Klien')
            ->assertSee('Ingin membuat pengajuan baru')
            ->assertSee('KTP')
            ->assertDontSee('private/never-public/ktp.jpg')
            ->assertDontSee('Mulai pemeriksaan');
        $this->actingAs($admin->user)
            ->get(route('admin.documents.index', ['filter' => 'all']))
            ->assertOk()
            ->assertDontSee('Klien Pembatalan');
        $this->actingAs($admin->user)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Pengajuan dibatalkan oleh klien')
            ->assertDontSee('private/never-public/ktp.jpg');
    }

    public function test_late_paid_webhook_records_payment_but_never_reactivates_cancelled_application(): void
    {
        $client = User::factory()->create(['email' => 'late-payment@example.test']);
        $service = Service::factory()->create();
        $application = $this->application($client, $service, ApplicationStatus::AWAITING_PAYMENT);
        $payment = $this->payment($application, PaymentStatus::PENDING, ['external_id' => 'invoice-late-cancel']);
        app(ApplicationCancellationService::class)->cancel($application, $client, ApplicationCancellationReason::NO_LONGER_CONTINUE);
        $payload = [
            'id' => $payment->external_id,
            'external_id' => $payment->reference_id,
            'status' => 'PAID',
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payer_email' => $client->email,
        ];
        $headers = ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'late-payment-after-cancel'];

        $this->actingAs($client)
            ->get(route('testing.fake-payments.checkout', $payment->public_id))
            ->assertRedirect(route('client.payments.show', $application->public_id));
        $this->actingAs($client)
            ->post(route('testing.fake-payments.complete', $payment->public_id))
            ->assertRedirect(route('client.applications.show', $application->public_id))
            ->assertSessionHasErrors('payment');
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);

        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::CANCELLED, $application->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payment.received_after_application_cancelled',
            'auditable_type' => (new Payment)->getMorphClass(),
            'auditable_id' => $payment->id,
        ]);
        $this->assertDatabaseMissing('application_status_histories', [
            'application_id' => $application->id,
            'to_status' => ApplicationStatus::PAYMENT_CONFIRMED->value,
        ]);
    }

    public function test_cancelled_application_is_excluded_from_client_and_admin_dashboard_workloads(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = $this->application($client, $service, ApplicationStatus::DRAFT);
        app(ApplicationCancellationService::class)->cancel($application, $client, ApplicationCancellationReason::NO_LONGER_CONTINUE);

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertViewHas('priorityApplication', null)
            ->assertViewHas('dashboardApplications', fn ($applications): bool => $applications->isEmpty());

        $admin = $this->admin();
        $this->actingAs($admin->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('priorityQueue', fn ($applications): bool => $applications->isEmpty())
            ->assertViewHas('attention', fn (array $attention): bool => $attention['review'] === 0 && $attention['revision'] === 0 && $attention['result'] === 0);
    }

    public function test_cancelled_detail_keeps_sensitive_identity_masked(): void
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = $this->application($client, $service, ApplicationStatus::DRAFT);
        PersonalApplicationDetail::create([
            'application_id' => $application->id,
            'name' => 'Klien Aman',
            'nik' => '3174000000001234',
            'family_card_number' => '3174000000005678',
            'email' => 'safe@example.test',
        ]);
        app(ApplicationCancellationService::class)->cancel($application, $client, ApplicationCancellationReason::REENTER_DATA);
        $admin = $this->admin();

        $this->actingAs($client)
            ->get(route('client.applications.show', $application->public_id))
            ->assertOk()
            ->assertDontSee('3174000000001234')
            ->assertDontSee('3174000000005678');
        $this->actingAs($admin->user)
            ->get(route('admin.applications.show', $application->public_id))
            ->assertOk()
            ->assertDontSee('3174000000001234')
            ->assertDontSee('3174000000005678')
            ->assertSee('************1234')
            ->assertSee('************5678');
    }

    private function application(User $client, Service $service, ApplicationStatus $status): Application
    {
        return Application::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => $status,
        ]);
    }

    private function payment(Application $application, PaymentStatus $status, array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'external_id' => 'invoice-'.fake()->uuid(),
            'reference_id' => 'BD-'.fake()->uuid(),
            'amount' => $application->price_amount_snapshot,
            'currency' => $application->currency,
            'payment_method' => PaymentMethod::BCA,
            'status' => $status,
        ], $attributes));
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        return Admin::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
    }
}
