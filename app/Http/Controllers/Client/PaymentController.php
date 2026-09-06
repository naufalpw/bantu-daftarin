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
        $state = $this->state($application, $payment);

        return view('client.payment.show', [
            'application' => $application,
            'payment' => $payment,
            'state' => $state,
            'qrCodeImage' => $state === 'cancelled' ? null : $this->qrCodeRenderer->render($payment),
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
        if ($application->status === ApplicationStatus::CANCELLED) {
            return redirect()->route('client.applications.show', $application->public_id)
                ->withErrors(['payment' => 'Pembayaran tidak dapat dilanjutkan untuk pengajuan yang telah dibatalkan.']);
        }
        $method = PaymentMethod::from($request->string('payment_method')->toString());
        $payment = $this->workflow->createPayment($application, $request->user(), $method);

        return redirect()->route('client.payments.show', $application->public_id)
            ->with('status', $payment->checkout_url
                ? 'Instruksi pembayaran dibuat. Lanjutkan melalui halaman pembayaran yang tersedia.'
                : 'Instruksi pembayaran sudah tersedia di halaman ini.');
    }

    private function state(Application $application, ?Payment $payment): string
    {
        if ($application->status === ApplicationStatus::CANCELLED) {
            return 'cancelled';
        }

        if (in_array($payment?->status, [
            PaymentStatus::REFUND_REQUESTED,
            PaymentStatus::REFUNDING,
            PaymentStatus::REFUNDED,
        ], true)) {
            return 'refund';
        }

        if ($payment?->status === PaymentStatus::PAID || in_array($application->status, [
            ApplicationStatus::PAYMENT_CONFIRMED,
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::REVISION_REQUIRED,
            ApplicationStatus::REVISION_SUBMITTED,
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::ESTIMATE_PENDING,
            ApplicationStatus::IN_PROGRESS,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS,
            ApplicationStatus::RESULT_UPLOADED,
            ApplicationStatus::RESULT_REVIEW,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED,
        ], true)) {
            return 'success';
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
