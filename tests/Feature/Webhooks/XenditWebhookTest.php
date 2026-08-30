<?php

namespace Tests\Feature\Webhooks;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertDatabaseCount('webhook_events', 1);
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
        $this->assertDatabaseHas('webhook_events', ['provider' => 'xendit', 'event_id' => 'event-malformed', 'status' => 'REJECTED']);
        $this->assertDatabaseMissing('payments', ['status' => PaymentStatus::PAID->value]);
    }
}
