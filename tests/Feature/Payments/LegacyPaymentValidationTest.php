<?php

namespace Tests\Feature\Payments;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyPaymentValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeReadyApplication(User $user): Application
    {
        $service = Service::factory()->create([
            'price_amount' => 150000,
            'currency' => 'IDR',
        ]);

        return Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT,
            'price_amount_snapshot' => 150000,
            'currency' => 'IDR',
        ]);
    }

    public function test_valid_bca_method_is_accepted_and_creates_payment(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::BCA->value,
        ]);

        $response->assertRedirect(route('client.payments.show', $application->public_id));
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
        $payment = $application->payments()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentMethod::BCA, $payment->payment_method);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
    }

    public function test_valid_bri_method_is_accepted_and_creates_payment(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::BRI->value,
        ]);

        $response->assertRedirect(route('client.payments.show', $application->public_id));
        $payment = $application->payments()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentMethod::BRI, $payment->payment_method);
    }

    public function test_valid_qris_method_is_accepted_and_creates_payment(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::QRIS->value,
        ]);

        $response->assertRedirect(route('client.payments.show', $application->public_id));
        $payment = $application->payments()->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentMethod::QRIS, $payment->payment_method);
    }

    public function test_paypal_method_follows_current_unsupported_provider_behavior(): void
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('PayPal belum tersedia.');

        $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::PAYPAL->value,
        ]);
    }

    public function test_missing_payment_method_is_rejected_with_validation_error(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), []);

        $response->assertSessionHasErrors(['payment_method']);
        $this->assertSame(0, $application->payments()->count());
        $this->assertSame(ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, $application->fresh()->status);
    }

    public function test_unknown_payment_method_is_rejected_without_silent_bca_fallback(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => 'UNKNOWN_METHOD',
        ]);

        $response->assertSessionHasErrors(['payment_method']);
        $this->assertSame(0, $application->payments()->count());
        $this->assertSame(ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT, $application->fresh()->status);
    }

    public function test_case_manipulated_payment_method_is_rejected_strictly(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => 'bca', // lowercase
        ]);

        $response->assertSessionHasErrors(['payment_method']);
        $this->assertSame(0, $application->payments()->count());
    }

    public function test_cross_owner_application_payment_remains_blocked(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $application = $this->makeReadyApplication($owner);

        $response = $this->actingAs($intruder)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::BCA->value,
        ]);

        $response->assertNotFound();
        $this->assertSame(0, $application->payments()->count());
    }

    public function test_payment_readiness_remains_enforced(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['price_amount' => 100000, 'currency' => 'IDR']);
        // Draft status: not ready for payment
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => PaymentMethod::BCA->value,
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertSame(0, $application->payments()->count());
        $this->assertSame(ApplicationStatus::DRAFT, $application->fresh()->status);
    }

    public function test_no_silent_bca_fallback_for_empty_string_payment_method(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $response = $this->actingAs($user)->post(route('client.applications.payment', $application->public_id), [
            'payment_method' => '',
        ]);

        $response->assertSessionHasErrors(['payment_method']);
        $this->assertSame(0, $application->payments()->count());
        $this->assertNull($application->payments()->where('payment_method', PaymentMethod::BCA)->first());
    }
}
