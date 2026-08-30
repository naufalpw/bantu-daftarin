<?php

namespace Tests\Feature\Payments;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use chillerlan\QRCode\QRCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentPhaseTwoBTest extends TestCase
{
    use RefreshDatabase;

    public static function xenditMethods(): array
    {
        return [
            'bca' => [PaymentMethod::BCA, 'BCA_VIRTUAL_ACCOUNT'],
            'bri' => [PaymentMethod::BRI, 'BRI_VIRTUAL_ACCOUNT'],
            'qris' => [PaymentMethod::QRIS, 'QRIS'],
        ];
    }

    public function test_client_cannot_open_another_clients_payment_page(): void
    {
        $owner = User::factory()->create();
        $otherClient = User::factory()->create();
        $application = $this->makeApplication($owner);

        $this->actingAs($otherClient)
            ->get(route('client.payments.show', $application->public_id))
            ->assertNotFound();
    }

    public function test_unpaid_application_can_create_payment_from_snapshot_amount(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user, amount: 135000);

        $this->actingAs($user)
            ->post(route('client.payments.store', $application->public_id), [
                'payment_method' => PaymentMethod::BCA->value,
                'amount' => 1,
            ])
            ->assertRedirect(route('client.payments.show', $application->public_id));

        $payment = $application->payments()->latest('id')->firstOrFail();
        $this->assertSame('135000.00', $payment->amount);
        $this->assertSame(PaymentMethod::BCA, $payment->payment_method);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
    }

    #[DataProvider('xenditMethods')]
    public function test_xendit_v3_mapping_uses_the_selected_channel_and_snapshot_amount(PaymentMethod $method, string $channel): void
    {
        Http::fake([
            'https://api.xendit.co/v3/payment_requests' => Http::response([
                'payment_request_id' => 'pr-'.strtolower($method->value),
                'status' => 'REQUIRES_ACTION',
                'reference_id' => 'will-be-replaced',
                'actions' => $method === PaymentMethod::QRIS
                    ? [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201010212synthetic']]
                    : [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '8800112233']],
                'channel_properties' => ['expires_at' => now()->addDay()->toIso8601String()],
            ], 201),
        ]);
        config([
            'services.xendit.driver' => 'xendit',
            'services.xendit.secret_key' => 'synthetic-xendit-secret',
        ]);

        $user = User::factory()->create();
        $application = $this->makeApplication($user, amount: 246800);
        $payment = app(ApplicationWorkflowService::class)->createPayment($application, $user, $method);

        Http::assertSent(function ($request) use ($channel): bool {
            $data = $request->data();

            return $request->url() === 'https://api.xendit.co/v3/payment_requests'
                && $request->header('api-version')[0] === '2024-11-11'
                && $data['type'] === 'PAY'
                && $data['channel_code'] === $channel
                && (float) $data['request_amount'] === 246800.0;
        });
        $this->assertSame('pr-'.strtolower($method->value), $payment->external_id);
        $this->assertSame($method, $payment->payment_method);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        if ($method === PaymentMethod::QRIS) {
            $this->assertSame('000201010212synthetic', $payment->qrString());
            $this->assertSame('REQUIRES_ACTION', $payment->gatewayStatus());
        }
    }

    public function test_unsupported_method_is_rejected_without_creating_payment(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $this->actingAs($user)
            ->post(route('client.payments.store', $application->public_id), ['payment_method' => 'CARD'])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_paypal_unavailable_does_not_create_a_fake_transaction(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $this->actingAs($user)
            ->post(route('client.payments.store', $application->public_id), ['payment_method' => PaymentMethod::PAYPAL->value])
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
    }

    public function test_repeated_submit_reuses_one_pending_payment(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);

        $first = app(ApplicationWorkflowService::class)->createPayment($application, $user, PaymentMethod::BCA);
        $second = app(ApplicationWorkflowService::class)->createPayment($application, $user, PaymentMethod::BCA);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_pending_payment_cannot_be_replaced_by_a_second_method(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        app(ApplicationWorkflowService::class)->createPayment($application, $user, PaymentMethod::BCA);

        $this->expectException(\DomainException::class);
        app(ApplicationWorkflowService::class)->createPayment($application, $user, PaymentMethod::BRI);
    }

    public function test_v3_capture_webhook_confirms_payment_and_is_idempotent(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-1',
            'reference_id' => 'BD-qris-1',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);
        $payload = [
            'event' => 'payment.capture',
            'created' => now()->toIso8601String(),
            'data' => [
                'payment_id' => 'py-qris-1',
                'payment_request_id' => $payment->external_id,
                'status' => 'SUCCEEDED',
                'reference_id' => $payment->reference_id,
                'request_amount' => 100000,
                'channel_code' => 'QRIS',
                'currency' => 'IDR',
            ],
        ];
        $headers = ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'v3-capture-1'];

        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();
        $this->postJson(route('webhooks.xendit'), $payload, $headers)->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_browser_redirect_query_cannot_mark_payment_paid(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $payment = app(ApplicationWorkflowService::class)->createPayment($application, $user, PaymentMethod::BCA);

        $this->actingAs($user)
            ->get(route('client.payments.show', $application->public_id).'?status=SUCCEEDED&payment_request_id='.$payment->external_id)
            ->assertOk()
            ->assertSee('Menunggu pembayaran');

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
    }

    public function test_failure_and_expiry_webhooks_never_mark_payment_successful(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $failed = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BRI,
            'external_id' => 'pr-bri-failed',
            'reference_id' => 'BD-bri-failed',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);

        $this->postJson(route('webhooks.xendit'), [
            'event' => 'payment.failure',
            'data' => [
                'payment_request_id' => $failed->external_id,
                'status' => 'FAILED',
                'reference_id' => $failed->reference_id,
                'request_amount' => 100000,
                'channel_code' => 'BRI_VIRTUAL_ACCOUNT',
                'currency' => 'IDR',
            ],
        ], ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'v3-failed-1'])->assertOk();

        $expired = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-bca-expired',
            'reference_id' => 'BD-bca-expired',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);
        $this->postJson(route('webhooks.xendit'), [
            'event' => 'payment_request.expiry',
            'data' => [
                'payment_request_id' => $expired->external_id,
                'status' => 'EXPIRED',
                'reference_id' => $expired->reference_id,
                'request_amount' => 100000,
                'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
                'currency' => 'IDR',
            ],
        ], ['x-callback-token' => 'testing-callback-token', 'x-event-id' => 'v3-expired-1'])->assertOk();

        $this->assertSame(PaymentStatus::FAILED, $failed->fresh()->status);
        $this->assertSame(PaymentStatus::EXPIRED, $expired->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
    }

    public function test_qris_action_is_available_to_the_payment_page_without_trusting_redirect_data(): void
    {
        config(['services.xendit.driver' => 'xendit']);
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-page',
            'reference_id' => 'BD-qris-page',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'provider_payload' => [
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201010212synthetic']],
            ],
        ]);

        $storedQrString = '000201010212server-owned-qris-payload';
        $payment->forceFill([
            'provider_payload' => [
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => $storedQrString]],
            ],
        ])->save();
        $expectedQrImage = (new QRCode)->render($storedQrString);

        $response = $this->actingAs($user)
            ->get(route('client.payments.show', $application->public_id).'?qr_string=attacker-controlled-payload')
            ->assertOk()
            ->assertSee('QRIS')
            ->assertSee('bd-payment-qr-code', false)
            ->assertSee($expectedQrImage, false);

        $this->assertStringNotContainsString('attacker-controlled-payload', $response->getContent());
        $this->assertStringNotContainsString($storedQrString, $response->getContent());

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_qris_payload_is_rendered_as_a_server_side_svg_without_changing_payment_state(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-image',
            'reference_id' => 'BD-qris-image',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'provider_payload' => [
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201010212synthetic-image']],
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('client.payments.show', $application->public_id))
            ->assertOk()
            ->assertSee('data-payment-state="waiting"', false)
            ->assertSee('bd-payment-qr-code', false);

        preg_match('/src="(data:image\/svg\+xml;base64,[^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $svg = base64_decode(substr($matches[1], strlen('data:image/svg+xml;base64,')), true);
        $this->assertNotFalse($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $application->fresh()->status);
    }

    public function test_expired_pending_qris_does_not_render_an_active_payment_image(): void
    {
        $user = User::factory()->create();
        $application = $this->makeApplication($user);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-expired-image',
            'reference_id' => 'BD-qris-expired-image',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->subMinute(),
            'provider_payload' => [
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201010212expired-image']],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('client.payments.show', $application->public_id))
            ->assertOk()
            ->assertSee('Pembayaran kedaluwarsa')
            ->assertDontSee('bd-payment-qr-code', false)
            ->assertDontSee('data-payment-state="waiting"', false);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_qris_image_is_only_available_to_the_authorized_client(): void
    {
        $owner = User::factory()->create();
        $otherClient = User::factory()->create();
        $application = $this->makeApplication($owner);
        Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-private-image',
            'reference_id' => 'BD-qris-private-image',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'provider_payload' => [
                'status' => 'REQUIRES_ACTION',
                'actions' => [['type' => 'PRESENT_TO_CUSTOMER', 'descriptor' => 'QR_STRING', 'value' => '000201010212private-image']],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('client.payments.show', $application->public_id))
            ->assertOk()
            ->assertSee('bd-payment-qr-code', false);

        $this->actingAs($otherClient)
            ->get(route('client.payments.show', $application->public_id))
            ->assertNotFound()
            ->assertDontSee('bd-payment-qr-code', false);
    }

    public function test_payment_state_frames_render_from_backend_lifecycle_state(): void
    {
        $user = User::factory()->create();
        $success = $this->makeApplication($user, status: ApplicationStatus::PAYMENT_CONFIRMED);
        Payment::create([
            'application_id' => $success->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-success-frame',
            'reference_id' => 'BD-success-frame',
            'amount' => $success->price_amount_snapshot,
            'currency' => 'IDR',
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)->get(route('client.payments.show', $success->public_id))
            ->assertOk()
            ->assertSee('data-name="/bayar-2"', false)
            ->assertSee('Pembayaran berhasil');

        $submitted = $this->makeApplication($user, status: ApplicationStatus::DOCUMENTS_SUBMITTED);
        $this->actingAs($user)->get(route('client.payments.show', $submitted->public_id))
            ->assertOk()
            ->assertSee('data-name="/bayar-3"', false)
            ->assertSee('Permohonan berhasil di ajukan');

        $estimate = $this->makeApplication($user, status: ApplicationStatus::ESTIMATE_PENDING);
        $estimate->forceFill(['estimated_completion_at' => now()->addWeek()])->save();
        $this->actingAs($user)->get(route('client.payments.show', $estimate->public_id))
            ->assertOk()
            ->assertSee('data-name="/bayar-4"', false)
            ->assertSee('Estimasi Selesai Tanggal');

        $result = $this->makeApplication($user, status: ApplicationStatus::COMPLETED);
        $this->actingAs($user)->get(route('client.payments.show', $result->public_id))
            ->assertOk()
            ->assertSee('data-name="/bayar-5"', false)
            ->assertSee('Hasil layanan tersedia di akun Anda');
    }

    private function makeApplication(User $user, int $amount = 100000, ApplicationStatus $status = ApplicationStatus::AWAITING_PAYMENT): Application
    {
        $service = Service::factory()->create([
            'code' => 'NPWP_PERSONAL_'.Str::lower(Str::random(8)),
            'name' => 'NPWP Pribadi',
            'price_amount' => $amount,
            'currency' => 'IDR',
        ]);

        return Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => $status,
            'price_amount_snapshot' => $amount,
            'currency' => 'IDR',
        ]);
    }
}
