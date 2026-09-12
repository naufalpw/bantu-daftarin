<?php

namespace Tests\Feature\Payments;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymentPayloadPruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayloadMinimizationTest extends TestCase
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

    public function test_payment_creation_persists_minimized_provider_payload(): void
    {
        config([
            'services.xendit.driver' => 'xendit',
            'services.xendit.secret_key' => 'xnd_test_secret',
        ]);

        $user = User::factory()->create(['name' => 'Alice Test', 'email' => 'alice@example.com']);
        $application = $this->makeReadyApplication($user);

        $fakeXenditResponse = [
            'id' => 'pr-test-12345',
            'payment_request_id' => 'pr-test-12345',
            'reference_id' => 'BD-ref-12345',
            'type' => 'PAY',
            'country' => 'ID',
            'currency' => 'IDR',
            'request_amount' => 150000,
            'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
            'channel_properties' => [
                'display_name' => 'Bantu Daftarin',
                'expires_at' => now()->addHour()->toIso8601String(),
                'sensitive_internal_config' => 'secret_value',
            ],
            'actions' => [
                [
                    'action' => 'AUTH',
                    'type' => 'RES_PATH',
                    'descriptor' => 'VIRTUAL_ACCOUNT_NUMBER',
                    'value' => '9999900001',
                    'url' => null,
                    'internal_debug_metadata' => 'should_be_stripped',
                ],
            ],
            'status' => 'PENDING',
            'customer' => [
                'full_name' => 'Alice Test',
                'phone_number' => '+628123456789',
                'identity_card_number' => '1234567890123456',
            ],
            'internal_provider_token' => 'xendit_secret_token_never_store',
        ];

        Http::fake([
            '*/v3/payment_requests*' => Http::response($fakeXenditResponse, 200),
        ]);

        $workflow = app(ApplicationWorkflowService::class);
        $payment = $workflow->createPayment($application, $user, PaymentMethod::BCA);

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('pr-test-12345', $payment->external_id);

        $storedPayload = $payment->fresh()->provider_payload;
        $this->assertIsArray($storedPayload);

        // Required operational fields are retained
        $this->assertSame('pr-test-12345', $storedPayload['id']);
        $this->assertSame('BCA_VIRTUAL_ACCOUNT', $storedPayload['channel_code']);
        $this->assertSame('9999900001', $payment->virtualAccountNumber());

        // Unnecessary sensitive/internal fields are stripped
        $this->assertArrayNotHasKey('internal_provider_token', $storedPayload);
        $this->assertArrayNotHasKey('customer', $storedPayload);
        $this->assertArrayNotHasKey('display_name', $storedPayload['channel_properties'] ?? []);
        $this->assertArrayNotHasKey('customer_name', $storedPayload['channel_properties'] ?? []);
        $this->assertArrayNotHasKey('sensitive_internal_config', $storedPayload['channel_properties'] ?? []);
        $this->assertArrayNotHasKey('internal_debug_metadata', $storedPayload['actions'][0] ?? []);
    }

    public function test_unknown_webhook_fields_never_cross_a_database_persistence_boundary(): void
    {
        $user = User::factory()->create(['email' => 'allowlist@example.com']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-allowlist',
            'reference_id' => 'BD-allowlist',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);

        $databaseBindings = [];
        DB::listen(function ($query) use (&$databaseBindings): void {
            foreach ($query->bindings as $binding) {
                if (is_string($binding)) {
                    $databaseBindings[] = $binding;
                }
            }
        });

        $payload = [
            'event' => 'payment.capture',
            'top_level_secret' => 'never-persist-top-level',
            'provider_metadata' => ['debug_token' => 'never-persist-nested-metadata'],
            'data' => [
                'id' => 'py-allowlist',
                'payment_id' => 'py-allowlist',
                'payment_request_id' => 'pr-allowlist',
                'reference_id' => 'BD-allowlist',
                'status' => 'SUCCEEDED',
                'amount' => 100000,
                'currency' => 'IDR',
                'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
                'unknown_data' => 'never-persist-data',
                'customer' => [
                    'email' => $user->email,
                    'mobile_number' => 'never-persist-customer',
                ],
                'channel_properties' => [
                    'expires_at' => now()->addHour()->toIso8601String(),
                    'display_name' => 'never-persist-display-name',
                    'customer_name' => 'never-persist-customer-name',
                    'provider_secret' => 'never-persist-channel-property',
                ],
                'actions' => [[
                    'action' => 'AUTH',
                    'type' => 'RES_PATH',
                    'descriptor' => 'VIRTUAL_ACCOUNT_NUMBER',
                    'value' => '887700001',
                    'internal_debug_metadata' => 'never-persist-action',
                ]],
            ],
        ];

        $this->postJson(route('webhooks.xendit'), $payload, [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-explicit-allowlist',
        ])->assertOk();

        $eventPayload = WebhookEvent::where('event_id', 'event-explicit-allowlist')->sole()->payload;
        $paymentPayload = $payment->fresh()->provider_payload;

        $this->assertSame($user->email, $eventPayload['data']['customer']['email']);
        $this->assertSame('887700001', $payment->fresh()->virtualAccountNumber());
        $this->assertSame(['expires_at' => $payload['data']['channel_properties']['expires_at']], $eventPayload['data']['channel_properties']);
        $this->assertSame(['expires_at' => $payload['data']['channel_properties']['expires_at']], $paymentPayload['channel_properties']);

        $persistedJson = json_encode([$eventPayload, $paymentPayload], JSON_THROW_ON_ERROR);
        $allDatabaseBindings = implode('|', $databaseBindings);
        foreach ([
            'never-persist-top-level',
            'never-persist-nested-metadata',
            'never-persist-data',
            'never-persist-customer',
            'never-persist-display-name',
            'never-persist-customer-name',
            'never-persist-channel-property',
            'never-persist-action',
        ] as $forbiddenValue) {
            $this->assertStringNotContainsString($forbiddenValue, $persistedJson);
            $this->assertStringNotContainsString($forbiddenValue, $allDatabaseBindings);
        }
    }

    public function test_webhook_event_redacts_excessive_customer_pii_while_retaining_verification_email(): void
    {
        $user = User::factory()->create(['email' => 'client@example.com']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-webhook-pii',
            'reference_id' => 'BD-webhook-pii',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);

        $webhookPayload = [
            'event' => 'payment.capture',
            'data' => [
                'id' => 'py-123',
                'payment_id' => 'py-123',
                'payment_request_id' => 'pr-webhook-pii',
                'reference_id' => 'BD-webhook-pii',
                'status' => 'SUCCEEDED',
                'amount' => 100000,
                'currency' => 'IDR',
                'channel_code' => 'BCA_VIRTUAL_ACCOUNT',
                'customer' => [
                    'email' => 'client@example.com',
                    'mobile_number' => '+62899999999',
                    'home_address' => 'Jl. Sudirman No 1',
                    'national_id' => '3171010101010001',
                ],
            ],
        ];

        $response = $this->postJson(route('webhooks.xendit'), $webhookPayload, [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-pii-redaction',
        ]);

        $response->assertOk();

        // Check stored WebhookEvent payload
        $event = WebhookEvent::where('event_id', 'event-pii-redaction')->firstOrFail();
        $storedEventPayload = $event->payload;

        // Email required for normalization is preserved
        $this->assertSame('client@example.com', $storedEventPayload['data']['customer']['email']);

        // Excess PII is omitted/redacted
        $this->assertArrayNotHasKey('mobile_number', $storedEventPayload['data']['customer']);
        $this->assertArrayNotHasKey('home_address', $storedEventPayload['data']['customer']);
        $this->assertArrayNotHasKey('national_id', $storedEventPayload['data']['customer']);
    }

    public function test_webhook_processing_preserves_existing_actions_on_payment(): void
    {
        $user = User::factory()->create(['email' => 'qris@example.com']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::QRIS,
            'external_id' => 'pr-qris-preserve',
            'reference_id' => 'BD-qris-preserve',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'provider_payload' => [
                'id' => 'pr-qris-preserve',
                'status' => 'REQUIRES_ACTION',
                'actions' => [
                    ['descriptor' => 'QR_STRING', 'type' => 'PRESENT_TO_CUSTOMER', 'value' => '000201010212preserved-qr-string'],
                ],
            ],
        ]);

        $webhookPayload = [
            'event' => 'payment.capture',
            'data' => [
                'id' => 'py-qris-123',
                'payment_id' => 'py-qris-123',
                'payment_request_id' => 'pr-qris-preserve',
                'reference_id' => 'BD-qris-preserve',
                'status' => 'SUCCEEDED',
                'amount' => 100000,
                'currency' => 'IDR',
                'channel_code' => 'QRIS',
                'customer' => ['email' => 'qris@example.com'],
            ],
        ];

        $response = $this->postJson(route('webhooks.xendit'), $webhookPayload, [
            'x-callback-token' => 'testing-callback-token',
            'x-event-id' => 'event-qris-preserve',
        ]);

        $response->assertOk();

        $freshPayment = $payment->fresh();
        $this->assertSame(PaymentStatus::PAID, $freshPayment->status);

        // QR action remains preserved on the payment record
        $this->assertSame('000201010212preserved-qr-string', $freshPayment->qrString());
    }

    public function test_no_callback_tokens_or_auth_headers_stored_in_database(): void
    {
        $user = User::factory()->create(['email' => 'secure@example.com']);
        $application = Application::create([
            'user_id' => $user->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::AWAITING_PAYMENT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);
        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-no-headers',
            'reference_id' => 'BD-no-headers',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
        ]);

        config([
            'services.xendit.callback_token' => 'super_secret_callback_token',
        ]);

        $this->postJson(route('webhooks.xendit'), [
            'id' => 'pr-no-headers',
            'external_id' => 'BD-no-headers',
            'status' => 'PAID',
            'amount' => 100000,
            'currency' => 'IDR',
            'payer_email' => $user->email,
        ], [
            'x-callback-token' => 'super_secret_callback_token',
            'x-event-id' => 'event-no-headers',
            'Authorization' => 'Bearer super_secret_bearer_token',
        ])->assertOk();

        $event = WebhookEvent::where('event_id', 'event-no-headers')->firstOrFail();
        $encodedEventPayload = json_encode($event->payload);

        $this->assertStringNotContainsString('super_secret_callback_token', $encodedEventPayload);
        $this->assertStringNotContainsString('super_secret_bearer_token', $encodedEventPayload);

        $encodedPaymentPayload = json_encode($payment->fresh()->provider_payload);
        $this->assertStringNotContainsString('super_secret_callback_token', $encodedPaymentPayload);
        $this->assertStringNotContainsString('super_secret_bearer_token', $encodedPaymentPayload);
    }

    public function test_historical_full_payload_shape_remains_backward_compatible(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        // Construct historical un-minimized payload
        $historicalPayload = [
            'id' => 'pr-historical-999',
            'status' => 'REQUIRES_ACTION',
            'actions' => [
                ['descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '8888800001', 'type' => 'VA'],
                ['descriptor' => 'QR_STRING', 'value' => '00020101historicalqr', 'type' => 'QR'],
            ],
            'excessive_historical_data' => [
                'blob' => str_repeat('A', 500),
            ],
        ];

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-historical-999',
            'reference_id' => 'BD-historical-999',
            'amount' => 150000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PENDING,
            'provider_payload' => $historicalPayload,
        ]);

        $this->assertSame('8888800001', $payment->virtualAccountNumber());
        $this->assertSame('00020101historicalqr', $payment->qrString());
        $this->assertSame('REQUIRES_ACTION', $payment->gatewayStatus());
        $this->assertCount(2, $payment->actions());
    }

    public function test_prune_payloads_command_dry_run_does_not_modify_records(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $event = WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'evt-old-dry',
            'status' => 'PROCESSED',
            'payload' => ['raw' => 'secret_data'],
            'received_at' => now()->subDays(100),
            'created_at' => now()->subDays(100),
        ]);

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-old-dry',
            'reference_id' => 'BD-old-dry',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::REFUNDED,
            'provider_payload' => ['raw' => 'payment_secret'],
            'updated_at' => now()->subDays(100),
        ]);

        $this->artisan('payments:prune-payloads --dry-run --days=90')
            ->expectsOutputToContain('[DRY-RUN] Eligible WebhookEvent records to prune: 1')
            ->expectsOutputToContain('[DRY-RUN] Eligible Payment provider_payload records to tombstone: 1')
            ->assertSuccessful();

        $this->assertSame(['raw' => 'secret_data'], $event->fresh()->payload);
        $this->assertSame(['raw' => 'payment_secret'], $payment->fresh()->provider_payload);
    }

    public function test_prune_payloads_command_force_executes_safe_tombstoning_and_audits(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $event = WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'evt-old-prune',
            'status' => 'PROCESSED',
            'payload' => ['raw' => 'secret_data'],
            'received_at' => now()->subDays(95),
            'created_at' => now()->subDays(95),
        ]);

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-old-prune',
            'reference_id' => 'BD-old-prune',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::REFUNDED,
            'provider_payload' => [
                'raw' => 'payment_secret',
                'actions' => [['descriptor' => 'VIRTUAL_ACCOUNT_NUMBER', 'value' => '123456']],
            ],
            'updated_at' => now()->subDays(95),
        ]);

        $this->artisan('payments:prune-payloads --force --days=90')
            ->expectsOutputToContain('Successfully pruned 1 webhook events and 1 payment provider payloads.')
            ->assertSuccessful();

        $freshEvent = $event->fresh();
        $this->assertTrue($freshEvent->payload['_pruned']);
        $this->assertArrayNotHasKey('raw', $freshEvent->payload);

        $freshPayment = $payment->fresh();
        $this->assertTrue($freshPayment->provider_payload['_pruned']);
        $this->assertArrayNotHasKey('raw', $freshPayment->provider_payload);
        $this->assertArrayNotHasKey('actions', $freshPayment->provider_payload);
        $this->assertNull($freshPayment->virtualAccountNumber());

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payment_payloads.pruned',
        ]);
    }

    public function test_prune_payloads_requires_explicit_days_option(): void
    {
        $this->artisan('payments:prune-payloads')
            ->expectsOutputToContain('The --days option is required. No approved retention policy exists')
            ->assertFailed();
    }

    public function test_prune_payloads_command_safety_guard_rejects_under_30_days(): void
    {
        $this->artisan('payments:prune-payloads --days=20')
            ->expectsOutputToContain('Command safety guard: retention age must be at least 30 days')
            ->assertFailed();
    }

    public function test_prune_payloads_defaults_to_simulation_when_force_not_specified(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $event = WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'evt-default-dry',
            'status' => 'PROCESSED',
            'payload' => ['raw' => 'secret_data'],
            'received_at' => now()->subDays(100),
            'created_at' => now()->subDays(100),
        ]);

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-default-dry',
            'reference_id' => 'BD-default-dry',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::REFUNDED,
            'provider_payload' => ['raw' => 'payment_secret'],
            'updated_at' => now()->subDays(100),
        ]);

        // Running without --force defaults to dry-run simulation
        $this->artisan('payments:prune-payloads --days=90')
            ->expectsOutputToContain('[DRY-RUN]')
            ->expectsOutputToContain('Dry-run simulation complete. No records were modified.')
            ->assertSuccessful();

        $this->assertSame(['raw' => 'secret_data'], $event->fresh()->payload);
        $this->assertSame(['raw' => 'payment_secret'], $payment->fresh()->provider_payload);
    }

    public function test_prune_payloads_preserves_incomplete_or_retryable_webhook_payloads(): void
    {
        // SEC-020 compatibility: RECEIVED and transient REJECTED events are not pruned
        $receivedEvent = WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'evt-received-old',
            'status' => 'RECEIVED',
            'payload' => ['raw' => 'needed_for_retry'],
            'received_at' => now()->subDays(100),
            'created_at' => now()->subDays(100),
        ]);

        $transientRejectedEvent = WebhookEvent::create([
            'provider' => 'xendit',
            'event_id' => 'evt-transient-rejected',
            'status' => 'REJECTED',
            'error_message' => 'transient_processing_failed',
            'payload' => ['raw' => 'needed_for_recovery'],
            'received_at' => now()->subDays(100),
            'created_at' => now()->subDays(100),
        ]);

        $this->artisan('payments:prune-payloads --force --days=90')
            ->expectsOutputToContain('Successfully pruned 0 webhook events')
            ->assertSuccessful();

        $this->assertSame(['raw' => 'needed_for_retry'], $receivedEvent->fresh()->payload);
        $this->assertSame(['raw' => 'needed_for_recovery'], $transientRejectedEvent->fresh()->payload);
    }

    public function test_prune_payloads_excludes_paid_and_in_progress_refund_states(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $payments = collect([
            PaymentStatus::PAID,
            PaymentStatus::REFUND_REQUESTED,
            PaymentStatus::REFUNDING,
        ])->map(function (PaymentStatus $status) use ($application): Payment {
            return Payment::create([
                'application_id' => $application->id,
                'provider' => 'xendit',
                'payment_method' => PaymentMethod::BCA,
                'external_id' => 'pr-preserve-'.strtolower($status->value),
                'reference_id' => 'BD-preserve-'.$status->value,
                'amount' => 100000,
                'currency' => 'IDR',
                'status' => $status,
                'provider_payload' => ['raw' => 'needed_for_refund'],
                'updated_at' => now()->subDays(100),
            ]);
        });

        $this->artisan('payments:prune-payloads --force --days=90')
            ->expectsOutputToContain('Successfully pruned 0 webhook events and 0 payment provider payloads.')
            ->assertSuccessful();

        foreach ($payments as $payment) {
            $this->assertSame(['raw' => 'needed_for_refund'], $payment->fresh()->provider_payload);
        }
    }

    public function test_locked_pruner_rejects_a_stale_candidate_that_entered_refund_processing(): void
    {
        $user = User::factory()->create();
        $application = $this->makeReadyApplication($user);

        $payment = Payment::create([
            'application_id' => $application->id,
            'provider' => 'xendit',
            'payment_method' => PaymentMethod::BCA,
            'external_id' => 'pr-stale-refund',
            'reference_id' => 'BD-stale-refund',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => PaymentStatus::PAID,
            'provider_payload' => ['raw' => 'required_for_refund'],
            'updated_at' => now()->subDays(100),
        ]);

        // Model a stale ID selected before another transaction starts the refund.
        $staleCandidateId = $payment->getKey();
        Payment::withoutTimestamps(function () use ($payment): void {
            Payment::query()->whereKey($payment->getKey())->update([
                'status' => PaymentStatus::REFUND_REQUESTED,
            ]);
        });

        $pruned = app(PaymentPayloadPruner::class)
            ->prunePayment($staleCandidateId, now()->subDays(90));

        $this->assertFalse($pruned);
        $this->assertSame(PaymentStatus::REFUND_REQUESTED, $payment->fresh()->status);
        $this->assertSame(['raw' => 'required_for_refund'], $payment->fresh()->provider_payload);
    }
}
