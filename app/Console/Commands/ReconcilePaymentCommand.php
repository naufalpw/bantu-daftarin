<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\ApplicationWorkflowService;
use Illuminate\Console\Command;

class ReconcilePaymentCommand extends Command
{
    protected $signature = 'payments:reconcile {reference : The payment reference ID}';

    protected $description = 'Reconcile an incomplete pending payment with the payment provider using its durable reference.';

    public function handle(ApplicationWorkflowService $workflow): int
    {
        $reference = (string) $this->argument('reference');
        $payment = Payment::query()->where('reference_id', $reference)->first();

        if (! $payment) {
            $this->error("Payment with reference [{$reference}] not found.");

            return self::FAILURE;
        }

        $reconciled = $workflow->reconcilePayment($payment);

        $this->info("Payment [{$reference}] reconciled successfully. Status: {$reconciled->status->value}.");

        return self::SUCCESS;
    }
}
