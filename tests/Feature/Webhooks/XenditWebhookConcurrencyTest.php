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
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Tests\TestCase;

class XenditWebhookConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_duplicate_delivery_produces_one_logical_payment_transition(): void
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
            'external_id' => 'invoice-concurrent',
            'reference_id' => 'BD-concurrent',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);
        $payload = [
            'id' => 'invoice-concurrent',
            'external_id' => $payment->reference_id,
            'status' => 'PAID',
            'amount' => 100000,
            'currency' => 'IDR',
            'payer_email' => $user->email,
        ];

        $deliver = static function () use ($payload): int {
            $request = Request::create(
                '/webhooks/xendit',
                'POST',
                server: [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_CALLBACK_TOKEN' => 'testing-callback-token',
                    'HTTP_X_EVENT_ID' => 'event-concurrent',
                ],
                content: json_encode($payload, JSON_THROW_ON_ERROR),
            );

            return app(XenditWebhookController::class)->handle($request)->getStatusCode();
        };

        $statuses = Concurrency::driver('process')->run([$deliver, $deliver]);

        $this->assertSame([200, 200], array_values($statuses));
        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $application->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseHas('webhook_events', ['event_id' => 'event-concurrent', 'status' => 'PROCESSED']);
        $this->assertDatabaseCount('application_status_histories', 1);
        $this->assertSame(1, AuditLog::query()->where('event', 'payment.webhook_processed')->count());
    }
}
