<?php

namespace Tests\Feature\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayAmbiguousException;
use App\Exceptions\PaymentGatewayDefinitiveException;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentProviderOutcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_provider_configuration_is_a_definitive_no_call_failure(): void
    {
        config(['services.xendit.driver' => 'xendit', 'services.xendit.secret_key' => null]);
        Http::fake();
        $fixture = $this->fixture();

        try {
            app(ApplicationWorkflowService::class)->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);
            $this->fail('Missing provider configuration must fail definitively.');
        } catch (PaymentGatewayDefinitiveException) {
            Http::assertNothingSent();
        }

        $this->assertDefinitivelyFailed($fixture['application']);
    }

    public function test_documented_validation_rejection_is_definitive(): void
    {
        $this->fakeXenditResponse(['error_code' => 'API_VALIDATION_ERROR'], 400);
        $fixture = $this->fixture();

        $this->expectDefinitiveFailure($fixture);

        $this->assertDefinitivelyFailed($fixture['application']);
    }

    public function test_documented_authentication_rejection_is_definitive(): void
    {
        $this->fakeXenditResponse(['error_code' => 'INVALID_API_KEY'], 401);
        $fixture = $this->fixture();

        $this->expectDefinitiveFailure($fixture);

        $this->assertDefinitivelyFailed($fixture['application']);
    }

    public function test_timeout_outcome_remains_recoverable_pending_with_stable_reference(): void
    {
        $fixture = $this->fixture();
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->method('createInvoice')->willThrowException(new PaymentGatewayAmbiguousException('Synthetic timeout'));
        $this->app->instance(PaymentGateway::class, $gateway);

        $this->expectAmbiguousFailure($fixture);

        $payment = Payment::query()->where('application_id', $fixture['application']->id)->sole();
        $reference = $payment->reference_id;
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertNull($payment->failed_at);

        try {
            app(ApplicationWorkflowService::class)->reconcilePayment($payment);
        } catch (PaymentGatewayAmbiguousException) {
        }

        $this->assertSame($reference, $payment->fresh()->reference_id);
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_transport_exception_is_classified_as_ambiguous(): void
    {
        config(['services.xendit.driver' => 'xendit', 'services.xendit.secret_key' => 'synthetic-secret']);
        Http::fake(fn () => throw new ConnectionException('Synthetic connection loss'));
        $fixture = $this->fixture();

        $this->expectAmbiguousFailure($fixture);

        $this->assertSame(PaymentStatus::PENDING, $fixture['application']->payments()->sole()->status);
    }

    public function test_malformed_success_response_is_classified_as_ambiguous(): void
    {
        $this->fakeXenditResponse('not-json', 201);
        $fixture = $this->fixture();

        $this->expectAmbiguousFailure($fixture);

        $this->assertSame(PaymentStatus::PENDING, $fixture['application']->payments()->sole()->status);
    }

    public function test_rate_limit_and_server_failure_are_conservatively_ambiguous(): void
    {
        foreach ([[400, 'PAYMENT_REQUEST_RATE_LIMITED'], [429, 'TOO_MANY_REQUESTS'], [503, 'CHANNEL_UNAVAILABLE']] as [$status, $errorCode]) {
            $this->fakeXenditResponse(['error_code' => $errorCode], $status);
            $fixture = $this->fixture();

            $this->expectAmbiguousFailure($fixture);

            $this->assertSame(PaymentStatus::PENDING, $fixture['application']->payments()->sole()->status);
        }
    }

    public function test_reconcile_applies_the_same_definitive_failure_taxonomy(): void
    {
        $fixture = $this->fixture();
        $payment = $fixture['application']->payments()->create([
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'reference_id' => 'BD-reconcile-definitive',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addHour(),
        ]);
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->method('createInvoice')->willThrowException(new PaymentGatewayDefinitiveException('Synthetic validation rejection'));
        $this->app->instance(PaymentGateway::class, $gateway);

        try {
            app(ApplicationWorkflowService::class)->reconcilePayment($payment);
            $this->fail('Definitive reconciliation failure must be surfaced.');
        } catch (PaymentGatewayDefinitiveException) {
        }

        $this->assertSame(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->failed_at);
    }

    /** @param array{user:User,application:Application} $fixture */
    private function expectDefinitiveFailure(array $fixture): void
    {
        try {
            app(ApplicationWorkflowService::class)->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);
            $this->fail('Expected a definitive provider failure.');
        } catch (PaymentGatewayDefinitiveException) {
        }
    }

    /** @param array{user:User,application:Application} $fixture */
    private function expectAmbiguousFailure(array $fixture): void
    {
        try {
            app(ApplicationWorkflowService::class)->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);
            $this->fail('Expected an ambiguous provider outcome.');
        } catch (PaymentGatewayAmbiguousException) {
        }
    }

    private function assertDefinitivelyFailed(Application $application): void
    {
        $payment = $application->payments()->sole();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertNotNull($payment->failed_at);
        $this->assertNull($payment->external_id);
        $this->assertNull($payment->checkout_url);
        $this->assertNull($payment->provider_payload);
        $this->assertSame(1, AuditLog::query()->where('event', 'payment.checkout_definitive_failed')->where('auditable_id', $payment->id)->count());
    }

    private function fakeXenditResponse(array|string $body, int $status): void
    {
        config(['services.xendit.driver' => 'xendit', 'services.xendit.secret_key' => 'synthetic-secret']);
        Http::fake(['*' => Http::response($body, $status)]);
    }

    /** @return array{user:User,application:Application} */
    private function fixture(): array
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['price_amount' => 100000]);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        return compact('user', 'application');
    }
}
