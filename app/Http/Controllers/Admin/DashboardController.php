<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Support\AdminActivityPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->integer('period', 30);
        $period = in_array($period, [7, 30, 90], true) ? $period : 30;
        $unreadClientMessages = ChatMessage::query()
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->whereHas('sender', fn ($query) => $query->where('role', UserRole::CLIENT->value))
            ->count();

        return view('admin.dashboard', [
            'attention' => [
                'review' => Application::query()->where('status', ApplicationStatus::DOCUMENTS_SUBMITTED)->count(),
                'revision' => Application::query()->where('status', ApplicationStatus::REVISION_SUBMITTED)->count(),
                'result' => Application::query()->where('status', ApplicationStatus::RESULT_REVIEW)->count(),
                'support' => $unreadClientMessages,
            ],
            'period' => $period,
            'periods' => [7 => '7 hari terakhir', 30 => '30 hari terakhir', 90 => '90 hari terakhir'],
            'activity' => AdminActivityPresenter::dashboard($period),
            'priorityQueue' => Application::query()
                ->with(['user', 'service'])
                ->whereIn('status', [
                    ApplicationStatus::DOCUMENTS_SUBMITTED,
                    ApplicationStatus::REVISION_SUBMITTED,
                    ApplicationStatus::UNDER_REVIEW,
                    ApplicationStatus::RESULT_REVIEW,
                ])
                ->orderByRaw("case
                    when status = 'DOCUMENTS_SUBMITTED' then 1
                    when status = 'REVISION_SUBMITTED' then 2
                    when status = 'UNDER_REVIEW' then 3
                    when status = 'RESULT_REVIEW' then 4
                    else 5 end")
                ->latest('updated_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
