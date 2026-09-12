<?php

namespace Tests\Feature\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\ApplicationCancellationReason;
use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Webhooks\XenditWebhookController;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\ApplicationCancellationService;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Support\CoordinatedPaymentGateway;
use Tests\TestCase;

class PaymentDualWriteHardeningTest extends TestCase
{
    use DatabaseMigrations;

    public function test_normal_provider_success_persists_durable_intent_and_reconciles_invoice(): void
    {
        $fixture = $this->createPaymentFixture();
        $workflow = app(ApplicationWorkflowService::class);

        $payment = $workflow->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertNotNull($payment->reference_id);
        $this->assertStringStartsWith('BD-', $payment->reference_id);
        $this->assertNotNull($payment->external_id);
        $this->assertNotNull($payment->checkout_url);
        $this->assertSame(1, Payment::where('application_id', $fixture['application']->id)->count());
        $this->assertSame(1, AuditLog::where('event', 'payment.created')->count());
        $this->assertSame(1, AuditLog::where('event', 'payment.checkout_created')->count());
    }

    public function test_repeated_submit_reuses_one_durable_pending_payment(): void
    {
        $fixture = $this->createPaymentFixture();
        $workflow = app(ApplicationWorkflowService::class);

        $first = $workflow->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);
        $second = $workflow->createPayment($fixture['application']->fresh(), $fixture['user'], PaymentMethod::BCA);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->reference_id, $second->reference_id);
        $this->assertSame($first->external_id, $second->external_id);
        $this->assertSame(1, Payment::where('application_id', $fixture['application']->id)->count());
    }

    public function test_ambiguous_provider_failure_keeps_durable_payment_reconcilable(): void
    {
        $fixture = $this->createPaymentFixture();

        $gatewayMock = $this->createMock(PaymentGateway::class);
        $gatewayMock->method('createInvoice')->willThrowException(new PaymentGatewayException('Provider API timeout'));
        $this->app->instance(PaymentGateway::class, $gatewayMock);

        $workflow = app(ApplicationWorkflowService::class);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Provider API timeout');

        try {
            $workflow->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);
        } finally {
            $payment = Payment::where('application_id', $fixture['application']->id)->first();
            $this->assertNotNull($payment);
            $this->assertSame(PaymentStatus::PENDING, $payment->status);
            $this->assertNull($payment->failed_at);
            $this->assertSame(1, AuditLog::where('event', 'payment.checkout_attempt_failed')->where('auditable_id', $payment->id)->count());
            $this->assertSame(0, AuditLog::where('event', 'payment.state_changed')->where('auditable_id', $payment->id)->count());
        }
    }

    public function test_concurrent_success_cannot_be_overwritten_by_ambiguous_provider_failure(): void
    {
        $fixture = $this->createPaymentFixture();
        $barrierDirectory = storage_path('framework/testing/payment-race-'.Str::uuid());
        File::ensureDirectoryExists($barrierDirectory);

        $applicationId = $fixture['application']->id;
        $userId = $fixture['user']->id;

        $success = static function () use ($applicationId, $userId, $barrierDirectory): array {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'success'));
            $payment = app(ApplicationWorkflowService::class)->createPayment(
                Application::findOrFail($applicationId),
                User::findOrFail($userId),
                PaymentMethod::BCA,
            );

            return ['outcome' => 'success', 'reference' => $payment->reference_id];
        };

        $failure = static function () use ($applicationId, $userId, $barrierDirectory): array {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'failure'));

            try {
                app(ApplicationWorkflowService::class)->createPayment(
                    Application::findOrFail($applicationId),
                    User::findOrFail($userId),
                    PaymentMethod::BCA,
                );
            } catch (PaymentGatewayException) {
                file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'failure.finished', '1', LOCK_EX);

                return [
                    'outcome' => 'failure',
                    'reference' => (string) Payment::where('application_id', $applicationId)->value('reference_id'),
                ];
            }

            throw new \RuntimeException('The synthetic failure attempt unexpectedly succeeded.');
        };

        try {
            $outcomes = Concurrency::driver('process')->run([$success, $failure]);

            $this->assertEqualsCanonicalizing(['success', 'failure'], array_column($outcomes, 'outcome'));
            $this->assertSame($outcomes[0]['reference'], $outcomes[1]['reference']);
            $this->assertSame($outcomes[0]['reference'], trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'success.ready')));
            $this->assertSame($outcomes[0]['reference'], trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'failure.ready')));

            $payment = Payment::where('application_id', $applicationId)->sole();
            $this->assertSame(PaymentStatus::PENDING, $payment->status);
            $this->assertSame('provider-'.$payment->reference_id, $payment->external_id);
            $this->assertNotNull($payment->checkout_url);
            $this->assertSame(1, Payment::where('application_id', $applicationId)->count());

            $this->postJson('/webhooks/xendit', [
                'id' => $payment->external_id,
                'external_id' => $payment->reference_id,
                'status' => 'PAID',
                'amount' => 100000,
                'currency' => 'IDR',
                'payer_email' => $fixture['user']->email,
            ], [
                'x-callback-token' => 'testing-callback-token',
                'x-event-id' => 'event-asymmetric-payment-race',
            ])->assertOk();

            $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
            $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $fixture['application']->fresh()->status);
        } finally {
            File::deleteDirectory($barrierDirectory);
        }
    }

    public function test_concurrent_success_supersedes_an_earlier_definitive_local_failure_without_hybrid_state(): void
    {
        $fixture = $this->createPaymentFixture();
        $barrierDirectory = storage_path('framework/testing/payment-definitive-race-'.Str::uuid());
        File::ensureDirectoryExists($barrierDirectory);
        $applicationId = $fixture['application']->id;
        $userId = $fixture['user']->id;

        $success = static function () use ($applicationId, $userId, $barrierDirectory): string {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'success'));

            return app(ApplicationWorkflowService::class)->createPayment(
                Application::findOrFail($applicationId),
                User::findOrFail($userId),
                PaymentMethod::BCA,
            )->reference_id;
        };

        $failure = static function () use ($applicationId, $userId, $barrierDirectory): string {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'definitive_failure'));

            try {
                app(ApplicationWorkflowService::class)->createPayment(
                    Application::findOrFail($applicationId),
                    User::findOrFail($userId),
                    PaymentMethod::BCA,
                );
            } catch (PaymentGatewayException) {
                file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'failure.finished', '1', LOCK_EX);

                return (string) Payment::query()->where('application_id', $applicationId)->value('reference_id');
            }

            throw new \RuntimeException('The synthetic definitive failure unexpectedly succeeded.');
        };

        try {
            $references = Concurrency::driver('process')->run([$success, $failure]);
        } finally {
            File::deleteDirectory($barrierDirectory);
        }

        $this->assertSame($references[0], $references[1]);
        $payment = Payment::query()->where('application_id', $applicationId)->sole();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('provider-'.$payment->reference_id, $payment->external_id);
        $this->assertNotNull($payment->checkout_url);
        $this->assertNull($payment->failed_at);
        $this->assertSame(1, Payment::query()->where('application_id', $applicationId)->count());
        $this->assertSame(1, AuditLog::query()->where('event', 'payment.checkout_definitive_failure_superseded')->where('auditable_id', $payment->id)->count());
    }

    public function test_provider_failed_webhook_between_local_failure_and_delayed_success_remains_authoritative(): void
    {
        $fixture = $this->createPaymentFixture();
        $barrierDirectory = storage_path('framework/testing/payment-webhook-provenance-race-'.Str::uuid());
        File::ensureDirectoryExists($barrierDirectory);
        $applicationId = $fixture['application']->id;
        $userId = $fixture['user']->id;

        $success = static function () use ($applicationId, $userId, $barrierDirectory): array {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'success_after_webhook'));
            $payment = app(ApplicationWorkflowService::class)->createPayment(
                Application::findOrFail($applicationId),
                User::findOrFail($userId),
                PaymentMethod::BCA,
            );

            return ['reference' => $payment->reference_id, 'status' => $payment->status->value];
        };

        $failure = static function () use ($applicationId, $userId, $barrierDirectory): string {
            app()->instance(PaymentGateway::class, new CoordinatedPaymentGateway($barrierDirectory, 'definitive_failure'));

            try {
                app(ApplicationWorkflowService::class)->createPayment(
                    Application::findOrFail($applicationId),
                    User::findOrFail($userId),
                    PaymentMethod::BCA,
                );
            } catch (PaymentGatewayException) {
                $reference = (string) Payment::query()->where('application_id', $applicationId)->value('reference_id');
                file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'failure.finished', $reference, LOCK_EX);

                return $reference;
            }

            throw new \RuntimeException('The synthetic definitive failure unexpectedly succeeded.');
        };

        $webhook = static function () use ($applicationId, $userId, $barrierDirectory): array {
            $deadline = microtime(true) + 10;
            $failureMarker = $barrierDirectory.DIRECTORY_SEPARATOR.'failure.finished';
            while (! is_file($failureMarker)) {
                if (microtime(true) >= $deadline) {
                    throw new \RuntimeException('Timed out waiting for the definitive payment failure.');
                }
                usleep(10_000);
            }

            $payment = Payment::query()->where('application_id', $applicationId)->sole();
            $user = User::findOrFail($userId);
            $payload = [
                'id' => 'provider-'.$payment->reference_id,
                'external_id' => $payment->reference_id,
                'status' => 'FAILED',
                'amount' => 100000,
                'currency' => 'IDR',
                'payer_email' => $user->email,
                'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
            ];
            $request = Request::create(
                '/webhooks/xendit',
                'POST',
                server: [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_ACCEPT' => 'application/json',
                    'HTTP_X_CALLBACK_TOKEN' => 'testing-callback-token',
                    'HTTP_X_EVENT_ID' => 'event-provider-failed-before-delayed-success',
                ],
                content: json_encode($payload, JSON_THROW_ON_ERROR),
            );
            $controller = app(XenditWebhookController::class);
            $firstStatus = $controller->handle($request)->getStatusCode();
            $secondStatus = $controller->handle($request)->getStatusCode();
            $payment = $payment->fresh();
            $event = WebhookEvent::query()->where('event_id', 'event-provider-failed-before-delayed-success')->sole();
            file_put_contents($barrierDirectory.DIRECTORY_SEPARATOR.'webhook.finished', $payment->reference_id, LOCK_EX);

            return [
                'reference' => $payment->reference_id,
                'payment_status' => $payment->status->value,
                'provider_status' => $payment->provider_payload['status'] ?? null,
                'provider_reference' => $payment->provider_payload['reference_id'] ?? null,
                'provider_id' => $payment->provider_payload['id'] ?? null,
                'event_status' => $event->status,
                'first_http_status' => $firstStatus,
                'second_http_status' => $secondStatus,
            ];
        };

        try {
            [$successOutcome, $failureReference, $webhookOutcome] = Concurrency::driver('process')->run([$success, $failure, $webhook]);

            $this->assertSame($failureReference, $successOutcome['reference']);
            $this->assertSame($failureReference, $webhookOutcome['reference']);
            $this->assertSame($failureReference, trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'success.ready')));
            $this->assertSame($failureReference, trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'failure.ready')));
            $this->assertSame($failureReference, trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'webhook.finished')));
            $this->assertSame($failureReference, trim((string) file_get_contents($barrierDirectory.DIRECTORY_SEPARATOR.'success.released')));
            $this->assertSame(PaymentStatus::FAILED->value, $webhookOutcome['payment_status']);
            $this->assertSame('FAILED', $webhookOutcome['provider_status']);
            $this->assertSame($failureReference, $webhookOutcome['provider_reference']);
            $this->assertSame('provider-'.$failureReference, $webhookOutcome['provider_id']);
            $this->assertSame('PROCESSED', $webhookOutcome['event_status']);
            $this->assertSame(200, $webhookOutcome['first_http_status']);
            $this->assertSame(200, $webhookOutcome['second_http_status']);

            $payment = Payment::query()->where('application_id', $applicationId)->sole();
            $this->assertSame(PaymentStatus::FAILED, $payment->status);
            $this->assertSame(PaymentStatus::FAILED->value, $successOutcome['status']);
            $this->assertNull($payment->external_id);
            $this->assertNull($payment->checkout_url);
            $this->assertSame(1, Payment::query()->where('application_id', $applicationId)->count());
            $this->assertSame(1, WebhookEvent::query()->where('event_id', 'event-provider-failed-before-delayed-success')->count());
            $this->assertSame(1, AuditLog::query()->where('event', 'payment.checkout_definitive_failed')->where('auditable_id', $payment->id)->count());
            $this->assertSame(1, AuditLog::query()->where('event', 'payment.webhook_processed')->where('auditable_id', $payment->id)->count());
            $this->assertSame(1, AuditLog::query()->where('event', 'payment.state_changed')->where('auditable_id', $payment->id)->count());
            $this->assertSame(0, AuditLog::query()->where('event', 'payment.checkout_definitive_failure_superseded')->where('auditable_id', $payment->id)->count());
            $this->assertSame(0, AuditLog::query()->where('event', 'payment.checkout_created')->where('auditable_id', $payment->id)->count());
            $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, $fixture['application']->fresh()->status);
            $this->assertDatabaseCount('application_status_histories', 0);
            $this->assertDatabaseCount('notifications', 0);
        } finally {
            File::deleteDirectory($barrierDirectory);
        }
    }

    public function test_provider_success_with_local_persistence_interruption_leaves_durable_reference(): void
    {
        $fixture = $this->createPaymentFixture();

        // Simulate Phase 1 committed, but process was interrupted before Phase 3 could save external_id
        $durablePayment = Payment::create([
            'application_id' => $fixture['application']->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'reference_id' => 'BD-durable-intent-test',
            'amount' => $fixture['application']->price_amount_snapshot,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addDay(),
            'external_id' => null,
            'checkout_url' => null,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $durablePayment->id,
            'reference_id' => 'BD-durable-intent-test',
            'external_id' => null,
        ]);

        $workflow = app(ApplicationWorkflowService::class);

        // Retrying createPayment must find the existing pending payment and reuse its exact reference_id
        $reconciled = $workflow->createPayment($fixture['application']->fresh(), $fixture['user'], PaymentMethod::BCA);

        $this->assertSame($durablePayment->id, $reconciled->id);
        $this->assertSame('BD-durable-intent-test', $reconciled->reference_id);
        $this->assertNotNull($reconciled->external_id);
        $this->assertNotNull($reconciled->checkout_url);
        $this->assertSame(1, Payment::where('application_id', $fixture['application']->id)->count());
    }

    public function test_operator_command_reconciles_incomplete_pending_payment(): void
    {
        $fixture = $this->createPaymentFixture();

        $durablePayment = Payment::create([
            'application_id' => $fixture['application']->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'reference_id' => 'BD-operator-reconcile-test',
            'amount' => $fixture['application']->price_amount_snapshot,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('payments:reconcile', ['reference' => 'BD-operator-reconcile-test'])
            ->expectsOutputToContain('reconciled successfully')
            ->assertSuccessful();

        $fresh = $durablePayment->fresh();
        $this->assertNotNull($fresh->external_id);
        $this->assertNotNull($fresh->checkout_url);
    }

    public function test_concurrent_payment_creation_requests_reuse_single_durable_payment(): void
    {
        $fixture = $this->createPaymentFixture();
        $appId = $fixture['application']->id;
        $userId = $fixture['user']->id;

        $create = static function () use ($appId, $userId): string {
            $app = Application::findOrFail($appId);
            $user = User::findOrFail($userId);
            $payment = app(ApplicationWorkflowService::class)->createPayment($app, $user, PaymentMethod::BCA);

            return $payment->reference_id;
        };

        $references = Concurrency::driver('process')->run([$create, $create]);

        // Both processes must produce the EXACT same reference_id
        $this->assertSame($references[0], $references[1]);
        $this->assertSame(1, Payment::where('application_id', $appId)->count());
        $this->assertSame(ApplicationStatus::AWAITING_PAYMENT, Application::findOrFail($appId)->status);
    }

    public function test_application_cancellation_blocks_further_payments_while_preserving_payment_state(): void
    {
        $fixture = $this->createPaymentFixture();
        $workflow = app(ApplicationWorkflowService::class);
        $payment = $workflow->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);

        $cancellation = app(ApplicationCancellationService::class);
        $cancellation->cancel($fixture['application']->fresh(), $fixture['user'], ApplicationCancellationReason::OTHER, 'Salah layanan');

        $this->assertSame(ApplicationStatus::CANCELLED, $fixture['application']->fresh()->status);
        // Provider payment record is not falsified upon application cancellation (per domain rules)
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);

        // Attempting to create payment on cancelled application is blocked
        $this->expectException(\DomainException::class);
        $workflow->createPayment($fixture['application']->fresh(), $fixture['user'], PaymentMethod::BCA);
    }

    public function test_webhook_can_reconcile_durable_reference_even_if_phase_3_unreconciled(): void
    {
        $fixture = $this->createPaymentFixture();

        // Durable payment exists locally before webhook arrives
        $durablePayment = Payment::create([
            'application_id' => $fixture['application']->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'reference_id' => 'BD-durable-for-webhook',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $payload = [
            'id' => 'xendit-invoice-wh',
            'external_id' => 'BD-durable-for-webhook',
            'status' => 'PAID',
            'amount' => 100000,
            'currency' => 'IDR',
            'payer_email' => $fixture['user']->email,
        ];

        $response = $this->postJson('/webhooks/xendit', $payload, [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-durable-webhook-1',
        ]);

        $response->assertOk();

        $freshPayment = $durablePayment->fresh();
        $this->assertSame(PaymentStatus::PAID, $freshPayment->status);
        $this->assertSame(ApplicationStatus::PAYMENT_CONFIRMED, $fixture['application']->fresh()->status);

        // If reconcilePayment runs later, it will not downgrade the PAID status
        $workflow = app(ApplicationWorkflowService::class);
        $workflow->reconcilePayment($freshPayment);
        $this->assertSame(PaymentStatus::PAID, $freshPayment->fresh()->status);
    }

    public function test_different_method_rejected_when_active_pending_payment_exists(): void
    {
        $fixture = $this->createPaymentFixture();
        $workflow = app(ApplicationWorkflowService::class);

        $workflow->createPayment($fixture['application'], $fixture['user'], PaymentMethod::BCA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Pembayaran yang sedang berjalan menggunakan metode lain.');

        $workflow->createPayment($fixture['application']->fresh(), $fixture['user'], PaymentMethod::BRI);
    }

    private function createPaymentFixture(): array
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL', 'price_amount' => 100000]);

        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        return [
            'user' => $user,
            'service' => $service,
            'application' => $application,
        ];
    }
}
