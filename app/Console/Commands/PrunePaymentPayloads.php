<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\PaymentPayloadPruner;
use Illuminate\Console\Command;

class PrunePaymentPayloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Retention period is deferred pending business/operations confirmation.
     * Defaults to dry-run (simulation) mode. Destructive pruning requires --force.
     */
    protected $signature = 'payments:prune-payloads
                            {--days= : Explicit retention age in days (required; no approved default policy)}
                            {--dry-run : Run in simulation mode without updating records (default behavior)}
                            {--force : Execute destructive pruning when explicitly requested by operator}';

    /**
     * The console command description.
     */
    protected $description = 'Safely prune aged webhook event payloads and settled payment provider payloads (dry-run by default; explicit --days required).';

    /**
     * Execute the console command.
     */
    public function handle(PaymentPayloadPruner $pruner): int
    {
        $daysOption = $this->option('days');
        if ($daysOption === null || $daysOption === '') {
            $this->error('The --days option is required. No approved retention policy exists (RETENTION PERIOD DEFERRED — BUSINESS/OPERATIONS CONFIRMATION REQUIRED).');

            return self::FAILURE;
        }

        $days = (int) $daysOption;
        if ($days < 30) {
            $this->error('Command safety guard: retention age must be at least 30 days to prevent accidental pruning of recent operational records.');

            return self::FAILURE;
        }

        $isForce = (bool) $this->option('force');
        $isDryRun = (bool) $this->option('dry-run') || ! $isForce;

        $cutoff = now()->subDays($days);
        $this->info(sprintf('Evaluating records older than %s (%d days)...', $cutoff->toIso8601String(), $days));

        // 1. Webhook Events older than cutoff that are strictly terminal.
        // Incomplete, retryable, or transiently rejected events (RECEIVED or transient REJECTED)
        // must retain their full payload for SEC-020 retry and reconciliation.
        $webhookQuery = WebhookEvent::query()
            ->where(function ($query): void {
                $query->where('status', 'PROCESSED')
                    ->orWhere(function ($sub): void {
                        $sub->where('status', 'REJECTED')
                            ->where('error_message', 'permanent_validation_failed');
                    });
            })
            ->where('created_at', '<=', $cutoff)
            ->whereNotNull('payload')
            ->whereRaw("COALESCE(payload->>'_pruned', 'false') <> 'true'");

        $webhookCount = $webhookQuery->count();

        // 2. Final Payments older than cutoff with unpruned payload.
        // PAID remains refund-capable and is intentionally excluded.
        $paymentQuery = Payment::query()
            ->whereIn('status', ['EXPIRED', 'FAILED', 'CANCELLED', 'REFUNDED'])
            ->where('updated_at', '<=', $cutoff)
            ->whereNotNull('provider_payload')
            ->whereRaw("COALESCE(provider_payload->>'_pruned', 'false') <> 'true'");

        $paymentCount = $paymentQuery->count();

        if ($isDryRun) {
            $this->info(sprintf('[DRY-RUN] Eligible WebhookEvent records to prune: %d', $webhookCount));
            $this->info(sprintf('[DRY-RUN] Eligible Payment provider_payload records to tombstone: %d', $paymentCount));
            $this->line('Dry-run simulation complete. No records were modified.');

            return self::SUCCESS;
        }

        $prunedWebhooks = 0;
        $webhookQuery->select('id')->chunkById(100, function ($events) use (&$prunedWebhooks, $cutoff, $pruner): void {
            foreach ($events as $event) {
                if ($pruner->pruneWebhook($event->getKey(), $cutoff)) {
                    $prunedWebhooks++;
                }
            }
        });

        $prunedPayments = 0;
        $paymentQuery->select('id')->chunkById(100, function ($payments) use (&$prunedPayments, $cutoff, $pruner): void {
            foreach ($payments as $payment) {
                if ($pruner->prunePayment($payment->getKey(), $cutoff)) {
                    $prunedPayments++;
                }
            }
        });

        AuditLog::create([
            'event' => 'payment_payloads.pruned',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'properties' => [
                'retention_days' => $days,
                'cutoff' => $cutoff->toIso8601String(),
                'pruned_webhook_events' => $prunedWebhooks,
                'pruned_payments' => $prunedPayments,
            ],
            'created_at' => now(),
        ]);

        $this->info(sprintf('Successfully pruned %d webhook events and %d payment provider payloads.', $prunedWebhooks, $prunedPayments));

        return self::SUCCESS;
    }
}
