<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'counts' => Application::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'queue' => Application::query()->with(['user', 'service'])->whereIn('status', [ApplicationStatus::DOCUMENTS_SUBMITTED, ApplicationStatus::REVISION_SUBMITTED, ApplicationStatus::RESULT_REVIEW])->latest()->take(20)->get(),
        ]);
    }
}
