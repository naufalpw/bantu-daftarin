<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ChatThread;
use App\Support\ApplicationStatusPresenter;
use App\Support\HelpFaq;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function index(): View
    {
        $helpApplications = collect();
        $helpThreads = collect();
        $hasMoreApplications = false;
        $conversationFilter = request()->string('conversation_filter', 'active')->toString();
        $conversationFilter = in_array($conversationFilter, ['active', 'archived'], true) ? $conversationFilter : 'active';

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
            $userId = auth()->id();
            $helpThreads = ChatThread::query()
                ->with(['application.service', 'latestMessage', 'participantStates' => fn ($states) => $states->where('user_id', $userId)])
                ->where('client_user_id', $userId)
                ->when($conversationFilter === 'archived', fn ($query) => $query->whereHas('participantStates', fn ($states) => $states->where('user_id', $userId)->whereNotNull('archived_at')))
                ->when($conversationFilter === 'active', fn ($query) => $query->whereDoesntHave('participantStates', fn ($states) => $states->where('user_id', $userId)->whereNotNull('archived_at')))
                ->orderByDesc('last_message_at')
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get();
        }

        return view('qna', [
            'questions' => HelpFaq::entries(),
            'categories' => HelpFaq::categories(),
            'helpApplications' => $helpApplications,
            'helpThreads' => $helpThreads,
            'hasMoreApplications' => $hasMoreApplications,
            'conversationFilter' => $conversationFilter,
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
