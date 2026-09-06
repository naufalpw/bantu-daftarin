<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentReviewAction;
use App\Enums\PaymentStatus;
use App\Enums\ResultVerificationStatus;
use App\Models\Application;
use App\Models\ApplicationEstimateHistory;
use App\Models\ApplicationStatusHistory;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AdminActivityPresenter
{
    /** @return array<string, string> */
    public static function categories(): array
    {
        return ['all' => 'Semua', 'application' => 'Pengajuan', 'document' => 'Dokumen', 'payment' => 'Pembayaran', 'process' => 'Proses', 'result' => 'Hasil'];
    }

    public static function paginate(string $category, int $perPage = 20): LengthAwarePaginator
    {
        $events = self::events()->when($category !== 'all', fn (Collection $items) => $items->where('category', $category))->sortByDesc('at')->values();
        $page = LengthAwarePaginator::resolveCurrentPage('page');

        return new LengthAwarePaginator($events->forPage($page, $perPage)->values(), $events->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Returns the dashboard's canonical operational activity view.
     *
     * @return array{days: int, total: int, points: Collection<int, array{date: CarbonInterface, key: string, label: string, count: int}>, recent: Collection<int, array{category: string, label: string, description: string, application: Application, client: string, at: CarbonInterface}>}
     */
    public static function dashboard(int $days, int $recentLimit = 5): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $until = now()->endOfDay();
        $events = self::dashboardEvents($from, $until);

        $dailyCounts = DB::query()
            ->fromSub($events, 'operational_events')
            ->selectRaw('DATE(event_at) as event_date, COUNT(*) as aggregate_count')
            ->groupBy('event_date')
            ->orderBy('event_date')
            ->pluck('aggregate_count', 'event_date');

        $points = collect(range(0, $days - 1))->map(function (int $offset) use ($from, $dailyCounts): array {
            $date = $from->copy()->addDays($offset);
            $key = $date->toDateString();

            return [
                'date' => $date,
                'key' => $key,
                'label' => $date->translatedFormat('j F Y'),
                'count' => (int) ($dailyCounts[$key] ?? 0),
            ];
        });

        $recentEvents = DB::query()
            ->fromSub(self::dashboardEvents($from, $until), 'operational_events')
            ->orderByDesc('event_at')
            ->limit($recentLimit)
            ->get();

        $applications = Application::query()
            ->with(['user', 'service'])
            ->whereIn('id', $recentEvents->pluck('application_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $recent = $recentEvents->map(function (object $event) use ($applications): ?array {
            $application = $applications->get($event->application_id);

            if (! $application instanceof Application) {
                return null;
            }

            return self::presentDashboardEvent($event, $application);
        })->filter()->values();

        return [
            'days' => $days,
            'total' => $points->sum('count'),
            'points' => $points,
            'recent' => $recent,
        ];
    }

    /** @return Collection<int, array{category: string, label: string, description: string, application: Application, client: string, at: CarbonInterface}> */
    private static function events(): Collection
    {
        $statusEvents = ApplicationStatusHistory::query()->with(['application.user', 'application.service'])->latest('created_at')->limit(120)->get()->map(function (ApplicationStatusHistory $history): array {
            $category = self::categoryForStatus($history->to_status);

            return [
                'category' => $category,
                'label' => self::labelForStatus($history->to_status),
                'description' => $history->to_status === ApplicationStatus::CANCELLED
                    ? 'Pengajuan dihentikan dan tetap tersimpan sebagai riwayat.'
                    : ($history->reason ?: $history->to_status->label()),
                'application' => $history->application,
                'client' => $history->application->user->name,
                'at' => $history->created_at,
            ];
        });

        $estimateEvents = ApplicationEstimateHistory::query()->with(['application.user', 'application.service'])->latest('created_at')->limit(80)->get()->map(fn (ApplicationEstimateHistory $history): array => [
            'category' => 'process',
            'label' => 'Estimasi diperbarui',
            'description' => 'Estimasi proses: '.$history->new_estimated_completion_at->translatedFormat('d M Y, H:i').'.',
            'application' => $history->application,
            'client' => $history->application->user->name,
            'at' => $history->created_at,
        ]);

        $paymentEvents = Payment::query()->with(['application.user', 'application.service'])->latest('updated_at')->limit(120)->get()->map(function (Payment $payment): array {
            $latePayment = $payment->status === PaymentStatus::PAID && $payment->application->status === ApplicationStatus::CANCELLED;

            return [
                'category' => 'payment',
                'label' => $latePayment ? 'Pembayaran diterima setelah pengajuan dibatalkan' : PaymentStatusPresenter::for($payment->status, $payment->expires_at?->isPast() ?? false)['label'],
                'description' => ($latePayment ? 'Perlu tindak lanjut manual. ' : '').$payment->currency.' '.number_format((float) $payment->amount, 0, ',', '.').' melalui '.($payment->payment_method?->label() ?? $payment->provider).'.',
                'application' => $payment->application,
                'client' => $payment->application->user->name,
                'at' => $payment->paid_at ?? $payment->failed_at ?? $payment->updated_at,
            ];
        });

        return $statusEvents->concat($estimateEvents)->concat($paymentEvents);
    }

    /**
     * One row is one canonical operational event. Client preparation states
     * are excluded; payment confirmation and result upload deliberately use
     * their own immutable workflow records rather than mirrored status rows.
     */
    private static function dashboardEvents(CarbonInterface $from, CarbonInterface $until)
    {
        $statusEvents = DB::table('application_status_histories')
            ->selectRaw("'status' as event_type, application_id, created_at as event_at, to_status as detail")
            ->whereBetween('created_at', [$from, $until])
            ->whereIn('to_status', [
                ApplicationStatus::DOCUMENTS_SUBMITTED->value,
                ApplicationStatus::UNDER_REVIEW->value,
                ApplicationStatus::DOCUMENTS_ACCEPTED->value,
                ApplicationStatus::REVISION_REQUIRED->value,
                ApplicationStatus::REVISION_SUBMITTED->value,
                ApplicationStatus::ESTIMATE_PENDING->value,
                ApplicationStatus::IN_PROGRESS->value,
                ApplicationStatus::WAITING_EXTERNAL_PROCESS->value,
                ApplicationStatus::RESULT_REVIEW->value,
                ApplicationStatus::COMPLETED->value,
                ApplicationStatus::ARCHIVED->value,
                ApplicationStatus::CANCELLED->value,
            ]);

        $estimateEvents = DB::table('application_estimate_histories')
            ->selectRaw("'estimate' as event_type, application_id, created_at as event_at, null as detail")
            ->whereBetween('created_at', [$from, $until]);

        $documentReviewEvents = DB::table('document_reviews')
            ->join('documents', 'documents.id', '=', 'document_reviews.document_id')
            ->selectRaw("'document_review' as event_type, documents.application_id, document_reviews.created_at as event_at, document_reviews.action as detail")
            ->whereBetween('document_reviews.created_at', [$from, $until]);

        $paymentEvents = DB::table('payments')
            ->selectRaw("'payment' as event_type, application_id, paid_at as event_at, status as detail")
            ->where('status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $until]);

        $resultUploadEvents = DB::table('result_documents')
            ->selectRaw("'result_uploaded' as event_type, application_id, uploaded_at as event_at, null as detail")
            ->whereBetween('uploaded_at', [$from, $until]);

        $resultVerificationEvents = DB::table('result_documents')
            ->selectRaw("'result_verification' as event_type, application_id, verified_at as event_at, verification_status as detail")
            ->whereIn('verification_status', [ResultVerificationStatus::VERIFIED->value, ResultVerificationStatus::REJECTED->value])
            ->whereNotNull('verified_at')
            ->whereBetween('verified_at', [$from, $until]);

        return $statusEvents
            ->unionAll($estimateEvents)
            ->unionAll($documentReviewEvents)
            ->unionAll($paymentEvents)
            ->unionAll($resultUploadEvents)
            ->unionAll($resultVerificationEvents);
    }

    /** @return array{category: string, label: string, description: string, application: Application, client: string, at: CarbonInterface} */
    private static function presentDashboardEvent(object $event, Application $application): array
    {
        $type = (string) $event->event_type;
        $detail = (string) ($event->detail ?? '');

        [$category, $label, $description] = match ($type) {
            'status' => self::presentDashboardStatus($detail),
            'estimate' => ['process', 'Estimasi diperbarui', 'Estimasi penyelesaian pengajuan diperbarui.'],
            'document_review' => match (DocumentReviewAction::tryFrom($detail)) {
                DocumentReviewAction::ACCEPT => ['document', 'Dokumen diterima', 'Dokumen aktif telah diterima saat pemeriksaan.'],
                DocumentReviewAction::REJECT => ['document', 'Dokumen ditolak', 'Dokumen aktif ditolak saat pemeriksaan.'],
                default => ['document', 'Revisi dokumen diminta', 'Dokumen aktif memerlukan perbaikan dari klien.'],
            },
            'payment' => $application->status === ApplicationStatus::CANCELLED
                ? ['payment', 'Pembayaran diterima setelah pengajuan dibatalkan', 'Pembayaran tercatat, pengajuan tetap dibatalkan dan memerlukan tindak lanjut manual.']
                : ['payment', 'Pembayaran dikonfirmasi', 'Pembayaran berhasil dicatat oleh sistem pembayaran.'],
            'result_uploaded' => ['result', 'Hasil diunggah', 'Hasil pengajuan diunggah untuk pemeriksaan.'],
            'result_verification' => $detail === ResultVerificationStatus::VERIFIED->value
                ? ['result', 'Hasil diverifikasi', 'Hasil telah diverifikasi oleh admin.']
                : ['result', 'Hasil perlu diperbaiki', 'Hasil belum dapat diverifikasi dan memerlukan perbaikan.'],
            default => ['application', 'Pembaruan pengajuan', 'Terdapat pembaruan pada workflow pengajuan.'],
        };

        return [
            'category' => $category,
            'label' => $label,
            'description' => $description,
            'application' => $application,
            'client' => $application->user->name,
            'at' => Carbon::parse($event->event_at),
        ];
    }

    /** @return array{string, string, string} */
    private static function presentDashboardStatus(string $value): array
    {
        $status = ApplicationStatus::tryFrom($value);

        if (! $status instanceof ApplicationStatus) {
            return ['application', 'Status pengajuan diperbarui', 'Terdapat pembaruan pada workflow pengajuan.'];
        }

        return [
            self::categoryForStatus($status),
            self::labelForStatus($status),
            $status->label(),
        ];
    }

    private static function categoryForStatus(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::DOCUMENTS_SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
            ApplicationStatus::DOCUMENTS_ACCEPTED,
            ApplicationStatus::REVISION_REQUIRED,
            ApplicationStatus::REVISION_SUBMITTED => 'document',
            ApplicationStatus::IN_PROGRESS,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS,
            ApplicationStatus::ESTIMATE_PENDING => 'process',
            ApplicationStatus::RESULT_UPLOADED,
            ApplicationStatus::RESULT_REVIEW,
            ApplicationStatus::COMPLETED,
            ApplicationStatus::ARCHIVED => 'result',
            ApplicationStatus::CANCELLED => 'application',
            default => 'application',
        };
    }

    private static function labelForStatus(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::DOCUMENTS_SUBMITTED => 'Dokumen dikirim untuk review',
            ApplicationStatus::UNDER_REVIEW => 'Pemeriksaan dokumen dimulai',
            ApplicationStatus::DOCUMENTS_ACCEPTED => 'Dokumen diterima',
            ApplicationStatus::REVISION_REQUIRED => 'Revisi dokumen diminta',
            ApplicationStatus::REVISION_SUBMITTED => 'Revisi dokumen dikirim',
            ApplicationStatus::IN_PROGRESS => 'Proses pengajuan dimulai',
            ApplicationStatus::WAITING_EXTERNAL_PROCESS => 'Menunggu proses instansi',
            ApplicationStatus::RESULT_UPLOADED => 'Hasil diunggah',
            ApplicationStatus::RESULT_REVIEW => 'Review hasil dimulai',
            ApplicationStatus::COMPLETED => 'Pengajuan selesai',
            ApplicationStatus::ARCHIVED => 'Pengajuan diarsipkan',
            ApplicationStatus::CANCELLED => 'Pengajuan dibatalkan oleh klien',
            default => 'Status pengajuan diperbarui',
        };
    }
}
