<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Support\ApplicationStatusPresenter;
use App\Support\HelpFaq;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function index(): View
    {
        $helpApplications = collect();
        $hasMoreApplications = false;

        if (auth()->check() && auth()->user()->isClient()) {
            $applications = Application::query()
                ->with(['service', 'chatThread'])
                ->where('user_id', auth()->id())
                ->whereHas('chatThread')
                ->latest('updated_at')
                ->limit(6)
                ->get();

            $hasMoreApplications = $applications->count() > 5;
            $helpApplications = $this->presentApplications($applications->take(5));
        }

        return view('qna', [
            'questions' => HelpFaq::entries(),
            'categories' => HelpFaq::categories(),
            'helpApplications' => $helpApplications,
            'hasMoreApplications' => $hasMoreApplications,
        ]);
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return Collection<int, array{application: Application, status: array<string, mixed>}>
     */
    private function presentApplications(Collection $applications): Collection
    {
        return $applications->map(static fn (Application $application): array => [
            'application' => $application,
            'status' => ApplicationStatusPresenter::for($application),
        ]);
    }
}
