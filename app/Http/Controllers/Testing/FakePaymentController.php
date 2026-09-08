<?php

namespace App\Http\Controllers\Testing;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Webhooks\XenditWebhookController;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FakePaymentController extends Controller
{
    public function show(string $paymentId): View|RedirectResponse
    {
        $this->assertFakeMode();
        $payment = $this->findPayment($paymentId);
        if ($payment->application->status === ApplicationStatus::CANCELLED) {
            return redirect()->route('client.payments.show', $payment->application->public_id);
        }

        return view('testing.fake-payment', compact('payment'));
    }

    public function complete(Request $request, string $paymentId): RedirectResponse
    {
        $this->assertFakeMode();
        $payment = $this->findPayment($paymentId);

        if ($payment->application->status === ApplicationStatus::CANCELLED) {
            return redirect()->route('client.applications.show', $payment->application->public_id)
                ->withErrors(['payment' => 'Pembayaran tidak dapat dilanjutkan untuk pengajuan yang telah dibatalkan.']);
        }

        if ($payment->status !== PaymentStatus::PENDING) {
            return back()->withErrors(['payment' => 'Pembayaran ini sudah tidak menunggu pembayaran.']);
        }

        $payload = [
            'id' => $payment->external_id,
            'external_id' => $payment->reference_id,
            'status' => 'PAID',
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payer_email' => $payment->application->user->email,
        ];
        $webhookRequest = Request::create(
            route('webhooks.xendit'),
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CALLBACK_TOKEN' => config('services.xendit.callback_token'),
                'HTTP_X_EVENT_ID' => 'fake-checkout-'.Str::uuid(),
            ],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $response = app(XenditWebhookController::class)->handle($webhookRequest);

        if ($response->getStatusCode() >= 300) {
            return back()->withErrors(['payment' => 'Pembayaran simulasi tidak dapat dikonfirmasi.']);
        }

        return redirect()->route('client.applications.show', $payment->application->public_id)
            ->with('status', 'Pembayaran simulasi berhasil dikonfirmasi.');
    }

    private function findPayment(string $paymentId): Payment
    {
        $payment = Payment::with(['application.service', 'application.user'])
            ->where('public_id', $paymentId)
            ->firstOrFail();
        $this->authorize('view', $payment);

        return $payment;
    }

    private function assertFakeMode(): void
    {
        abort_unless(in_array(app()->environment(), ['local', 'testing'], true) && config('services.xendit.driver') === 'fake', 404);
    }
}
