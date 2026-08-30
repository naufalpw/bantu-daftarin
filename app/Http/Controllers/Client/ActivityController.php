<?php

namespace App\Http\Controllers\Client;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $applications = $this->applicationsForCurrentClient();

        return view('client.activity.index', [
            'transactions' => $this->transactions($applications),
            'processes' => $this->processes($applications),
            'orders' => $this->orders($applications),
        ]);
    }

    public function show(string $publicId): View
    {
        $application = Application::query()->where('public_id', $publicId)->firstOrFail();
        $this->authorize('view', $application);
        $application->load(['service', 'payments', 'statusHistories']);

        return view('client.activity.show', [
            'application' => $application,
            'payments' => $application->payments->sortByDesc(fn (Payment $payment) => $payment->created_at)->values(),
            'histories' => $application->statusHistories->sortByDesc(fn ($history) => $history->created_at)->values(),
        ]);
    }

    /** @return Collection<int, Application> */
    private function applicationsForCurrentClient(): Collection
    {
        return Application::query()
            ->with(['service', 'payments', 'statusHistories'])
            ->where('user_id', request()->user()->getKey())
            ->latest()
            ->get();
    }

    /** @param Collection<int, Application> $applications */
    private function transactions(Collection $applications): Collection
    {
        return $applications
            ->flatMap(fn (Application $application) => $application->payments->map(fn (Payment $payment): array => [
                'payment' => $payment,
                'application' => $application,
                'status_label' => $this->paymentStatusLabel($payment->status),
                'method_label' => $payment->payment_method?->label() ?? 'Belum dipilih',
            ]))
            ->sortByDesc(fn (array $transaction) => $transaction['payment']->created_at)
            ->values();
    }

    /** @param Collection<int, Application> $applications */
    private function processes(Collection $applications): Collection
    {
        return $applications
            ->flatMap(fn (Application $application) => $application->statusHistories->map(fn ($history): array => [
                'history' => $history,
                'application' => $application,
                'label' => $history->to_status?->label() ?? (string) $history->to_status,
            ]))
            ->sortByDesc(fn (array $process) => $process['history']->created_at)
            ->values();
    }

    /** @param Collection<int, Application> $applications */
    private function orders(Collection $applications): Collection
    {
        return $applications->map(function (Application $application): array {
            $payment = $application->payments->sortByDesc(fn (Payment $item) => $item->created_at)->first();

            return [
                'application' => $application,
                'payment' => $payment,
                'status_label' => $application->status->label(),
                'payment_status_label' => $payment ? $this->paymentStatusLabel($payment->status) : 'Belum ada pembayaran',
            ];
        });
    }

    private function paymentStatusLabel(?PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::PENDING => 'Menunggu pembayaran',
            PaymentStatus::PAID => 'Berhasil',
            PaymentStatus::FAILED => 'Gagal',
            PaymentStatus::EXPIRED => 'Kedaluwarsa',
            PaymentStatus::CANCELLED => 'Dibatalkan',
            PaymentStatus::REFUND_REQUESTED => 'Menunggu pengembalian',
            PaymentStatus::REFUNDING => 'Sedang dikembalikan',
            PaymentStatus::REFUNDED => 'Dikembalikan',
            default => 'Belum tersedia',
        };
    }
}
