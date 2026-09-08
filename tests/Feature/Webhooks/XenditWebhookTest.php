<?php

namespace Tests\Feature\Webhooks;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Webhooks\XenditWebhookController;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\ApplicationTransitionService;
use App\Services\ApplicationWorkflowService;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_callback_token_is_rejected(): void
    {
        $response = $this->postJson(route('webhooks.xendit'), [], ['x-callback-token' => 'wrong']);

        $response->assertUnauthorized();
    }

    public function test_valid_paid_webhook_is_idempotent_and_confirms_application(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-1', 'reference_id' => 'BD-reference-1', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $payload = ['id' => 'invoice-1', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];

        $headers = ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-1'];
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => PaymentStatus::PAID->value]);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => ApplicationStatus::PAYMENT_CONFIRMED->value]);
        $this->assertDatabaseHas('webhook_events', ['provider' => 'xendit', 'event_id' => 'event-1', 'status' => 'PROCESSED']);
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseCount('application_status_histories', 1);
        $this->assertSame(1, AuditLog::query()->where('event', 'payment.webhook_processed')->count());
    }

    public function test_existing_received_event_is_resumed_from_its_ledger_payload(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-received', 'reference_id' => 'BD-received', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $payload = ['id' => 'invoice-received', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];

        WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'event-received',
            'event_type' => 'PAID',
            'payload' => $payload,
            'received_at' => now(),
            'status' => 'RECEIVED',
        ]);

        $this->postJson(route('webhooks.xendit'), ['id' => 'replacement-must-not-be-used'], [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-received',
        ])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
        $this->assertDatabaseHas('webhook_events', ['event_id' => 'event-received', 'status' => 'PROCESSED', 'error_message' => null]);
        $this->assertDatabaseCount('application_status_histories', 1);
    }

    public function test_legacy_retryable_rejected_event_is_reconciled_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-retry', 'reference_id' => 'BD-retry', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $payload = ['id' => 'invoice-retry', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];

        WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'event-legacy-rejected',
            'event_type' => 'PAID',
            'payload' => $payload,
            'received_at' => now(),
            'status' => 'REJECTED',
            'error_message' => 'validation_or_processing_failed',
        ]);

        $headers = ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-legacy-rejected'];
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
        $this->assertDatabaseHas('webhook_events', ['event_id' => 'event-legacy-rejected', 'status' => 'PROCESSED', 'error_message' => null]);
        $this->assertDatabaseCount('application_status_histories', 1);
    }

    public function test_transient_internal_failure_remains_received_and_succeeds_on_retry(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-transient', 'reference_id' => 'BD-transient', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $payload = ['id' => 'invoice-transient', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];
        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('applicationStatus')->once();
        $notifications->shouldReceive('paymentConfirmed')->once()->andThrow(new \RuntimeException('Synthetic transient failure.'));
        $audit = app(AuditService::class);
        $controller = new XenditWebhookController(
            new ApplicationTransitionService($audit, $notifications),
            $audit,
            $notifications,
        );
        $request = Request::create(
            '/webhooks/xendit',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_TOKEN' => 'testing-callback-token',
                'HTTP_X_EVENT_ID' => 'event-transient',
            ],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertSame(500, $controller->handle($request)->getStatusCode());
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $this->assertDatabaseHas('webhook_events', [
            'event_id' => 'event-transient',
            'status' => 'RECEIVED',
            'error_message' => 'transient_processing_failed',
        ]);
        $this->assertDatabaseCount('application_status_histories', 0);

        $this->postJson(route('webhooks.xendit'), $payload, [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-transient',
        ])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
        $this->assertDatabaseHas('webhook_events', ['event_id' => 'event-transient', 'status' => 'PROCESSED', 'error_message' => null]);
        $this->assertDatabaseCount('application_status_histories', 1);
    }

    public function test_client_payment_creation_does_not_mark_application_paid(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create(['price_amount' => 125000, 'currency' => 'IDR']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $payment = app(ApplicationWorkflowService::class)->createPayment($application, $user);

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $this->assertNotNull($payment->checkout_url);
        $this->assertSame('100000.00', $payment->amount);
    }

    public function test_local_fake_checkout_is_same_origin_and_confirms_payment_via_webhook(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create(['price_amount' => 125000, 'currency' => 'IDR']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $payment = app(ApplicationWorkflowService::class)->createPayment($application, $user);

        $this->assertSame(route('testing.fake-payments.checkout', $payment->public_id), $payment->checkout_url);
        $this->actingAs($user)->get($payment->checkout_url)->assertOk()->assertSee('Simulasi pembayaran');
        $this->actingAs($user)->post(route('testing.fake-payments.complete', $payment->public_id))
            ->assertRedirect(route('client.applications.show', $application->public_id));

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
    }

    public function test_existing_placeholder_fake_checkout_is_migrated_to_local_route(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'external_id' => 'fake-old-invoice',
            'reference_id' => 'BD-old-placeholder',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'checkout_url' => 'https://example.test/checkout/BD-old-placeholder',
        ]);

        $refreshed = app(ApplicationWorkflowService::class)->createPayment($application, $user);

        $this->assertSame($payment->id, $refreshed->id);
        $this->assertSame(route('testing.fake-payments.checkout', $payment->public_id), $refreshed->fresh()->checkout_url);
    }

    public function test_wrong_amount_currency_reference_and_identity_are_rejected(): void
    {
        $user = User::factory()->create(['email' => 'payer@example.test']);
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-validation', 'reference_id' => 'BD-validation', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);

        $baseHeaders = ['x-callback-token' => 'testing-callback-token'];
        $basePayload = ['id' => 'invoice-validation', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];

        $this->postJson(route('webhooks.xendit'), [...$basePayload, 'amount' => 99999], [...$baseHeaders, 'x-event-id' => 'event-wrong-amount'])->assertUnprocessable();
        $this->postJson(route('webhooks.xendit'), [...$basePayload, 'currency' => 'USD'], [...$baseHeaders, 'x-event-id' => 'event-wrong-currency'])->assertUnprocessable();
        $this->postJson(route('webhooks.xendit'), [...$basePayload, 'external_id' => 'BD-not-found'], [...$baseHeaders, 'x-event-id' => 'event-wrong-reference'])->assertUnprocessable();
        $this->postJson(route('webhooks.xendit'), [...$basePayload, 'payer_email' => 'other@example.test'], [...$baseHeaders, 'x-event-id' => 'event-wrong-identity'])->assertUnprocessable();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 4);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_failed_and_expired_webhooks_transition_only_pending_payment(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-failed', 'reference_id' => 'BD-failed', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);

        $payload = ['id' => 'invoice-failed', 'external_id' => $payment->reference_id, 'status' => 'FAILED', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];
        $this->postJson(route('webhooks.xendit'), $payload, ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-failed'])->assertOk();
        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);

        $expired = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-expired', 'reference_id' => 'BD-expired', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $payload['id'] = 'invoice-expired';
        $payload['external_id'] = $expired->reference_id;
        $payload['status'] = 'EXPIRED';
        $this->postJson(route('webhooks.xendit'), $payload, ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-expired'])->assertOk();
        $this->assertSame(PaymentStatus::EXPIRED, $expired->fresh()->status);
    }

    public function test_late_failure_callback_cannot_downgrade_paid_payment(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::PAYMENT_CONFIRMED, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-paid', 'reference_id' => 'BD-paid', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PAID, 'paid_at' => now()]);

        $payload = ['id' => 'invoice-paid', 'external_id' => $payment->reference_id, 'status' => 'FAILED', 'amount' => 100000, 'currency' => 'IDR', 'payer_email' => $user->email];
        $this->postJson(route('webhooks.xendit'), $payload, ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-late-failure'])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
    }

    public function test_malformed_webhook_is_ledgered_as_rejected_without_touching_payment(): void
    {
        $response = $this->postJson(route('webhooks.xendit'), ['id' => 'event-malformed'], [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-malformed',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'xendit',
            'event_id' => 'event-malformed',
            'status' => 'REJECTED',
            'error_message' => 'permanent_validation_failed',
        ]);
        $this->assertDatabaseMissing('payments', ['status' => PaymentStatus::PAID->value]);
    }

    public function test_permanently_rejected_event_is_not_reprocessed_with_replacement_payload(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $application = Application::create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => ApplicationStatus::AWAITING_PAYMENT, 'price_amount_snapshot' => 100000, 'currency' => 'IDR']);
        $payment = Payment::create(['application_id' => $application->id, 'provider' => 'xendit', 'external_id' => 'invoice-permanent', 'reference_id' => 'BD-permanent', 'amount' => 100000, 'currency' => 'IDR', 'status' => PaymentStatus::PENDING]);
        $invalidPayload = ['id' => 'invoice-permanent', 'external_id' => $payment->reference_id, 'status' => 'PAID', 'amount' => 99999, 'currency' => 'IDR', 'payer_email' => $user->email];
        $validReplacement = [...$invalidPayload, 'amount' => 100000];
        $headers = ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'event-permanent'];

        $this->postJson(route('webhooks.xendit'), $invalidPayload, $headers)->assertUnprocessable();
        $this->postJson(route('webhooks.xendit'), $validReplacement, $headers)->assertUnprocessable();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'xendit',
            'event_id' => 'event-permanent',
            'status' => 'REJECTED',
            'error_message' => 'permanent_validation_failed',
        ]);
        $this->assertDatabaseCount('application_status_histories', 0);
    }
}
