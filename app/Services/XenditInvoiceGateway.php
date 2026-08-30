<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\PaymentGatewayException;
use App\Models\Application;
use App\Models\Payment;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class XenditInvoiceGateway implements PaymentGateway
{
    public function createInvoice(Application $application, Payment $payment): array
    {
        $secret = config('services.xendit.secret_key');
        if (blank($secret)) {
            throw new PaymentGatewayException('Payment provider belum dikonfigurasi.');
        }

        try {
            Configuration::setXenditKey($secret);
            $request = new CreateInvoiceRequest([
                'external_id' => $payment->reference_id,
                'amount' => (float) $payment->amount,
                'payer_email' => $application->user->email,
                'description' => 'Bantu Daftarin - '.$application->service->name,
                'invoice_duration' => (int) config('services.xendit.invoice_duration'),
                'currency' => $payment->currency,
                'should_send_email' => false,
                'success_redirect_url' => route('client.applications.show', $application->public_id),
                'failure_redirect_url' => route('client.applications.show', $application->public_id),
            ]);
            $invoice = (new InvoiceApi)->createInvoice($request);

            return [
                'external_id' => (string) $invoice->getId(),
                'checkout_url' => (string) $invoice->getInvoiceUrl(),
                'expires_at' => $invoice->getExpiryDate(),
                'payload' => [
                    'id' => $invoice->getId(),
                    'external_id' => $invoice->getExternalId(),
                    'amount' => $invoice->getAmount(),
                    'currency' => $invoice->getCurrency(),
                ],
            ];
        } catch (\Throwable $exception) {
            logger()->error('xendit_gateway_failure', ['exception_class' => $exception::class]);
            throw new PaymentGatewayException('Payment provider tidak dapat dihubungi.');
        }
    }
}
