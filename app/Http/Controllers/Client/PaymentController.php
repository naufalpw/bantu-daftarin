<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StorePaymentRequest;
use App\Models\Application;
use App\Models\Payment;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymentQrCodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly ApplicationWorkflowService $workflow,
        private readonly PaymentQrCodeRenderer $qrCodeRenderer,
    ) {}

    public function show(string $publicId): View
    {
        $application = $this->find($publicId);
        $this->authorize('view', $application);
        $application->load(['service', 'payments']);

        $payment = $application->payments->sortByDesc('id')->first();

        return view('client.payment.show', [
            'application' => $application,
            'payment' => $payment,
            'state' => $this->state($application, $payment),
            'qrCodeImage' => $this->qrCodeRenderer->render($payment),
            'paymentMethods' => [
                PaymentMethod::BCA,
                PaymentMethod::PAYPAL,
                PaymentMethod::QRIS,
                PaymentMethod::BRI,
            ],
        ]);
    }

    public function store(StorePaymentRequest $request, string $publicId): RedirectResponse
    {
        $application = $this->find($publicId);
        $this->authorize('submit', $application);
        $method = PaymentMethod::from($request->string('payment_method')->toString());
        $payment = $this->workflow->createPayment($application, $request->user(), $method);

        return redirect()->route('client.payments.show', $application->public_id)
            ->with('status', $payment->checkout_url
                ? 'Payment request dibuat. Lanjutkan pembayaran melalui halaman provider.'
                : 'Instruksi pembayaran sudah tersedia di halaman ini.');
    }

    private function state(Application $application, ?Payment $payment): string
    {
        if ($payment?->status === PaymentStatus::PAID || $application->status === ApplicationStatus::PAYMENT_CONFIRMED) {
            return 'success';
        }

        if ($application->status === ApplicationStatus::DOCUMENTS_SUBMITTED) {
            return 'submitted';
        }

        if (in_array($application->status, [
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::ESTIMATE_PENDING,
            ApplicationStatus::IN_PROGRESS,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS,
        ], true)) {
            return 'estimate';
        }

        if (in_array($application->status, [
            ApplicationStatus::RESULT_UPLOADED,
            ApplicationStatus::RESULT_REVIEW,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED,
        ], true)) {
            return 'result';
        }

        if (in_array($payment?->status, [PaymentStatus::FAILED, PaymentStatus::EXPIRED], true)) {
            return 'failure';
        }

        if ($payment?->status === PaymentStatus::PENDING && $payment->expires_at?->isPast()) {
            return 'failure';
        }

        return $payment?->status === PaymentStatus::PENDING ? 'waiting' : 'selection';
    }

    private function find(string $publicId): Application
    {
        return Application::query()
            ->where('public_id', $publicId)
            ->where('user_id', request()->user()->getKey())
            ->firstOrFail();
    }
}
