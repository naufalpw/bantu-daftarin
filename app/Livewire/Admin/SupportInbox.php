<?php

namespace App\Livewire\Admin;

use App\Models\ChatThread;
use App\Services\ChatThreadArchiveService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class SupportInbox extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'filter', history: true, except: 'all')]
    public string $filter = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, string> */
    public array $filters = [
        'all' => 'Semua',
        'unread' => 'Belum dibaca',
        'application' => 'Pengajuan',
        'general' => 'Bantuan Umum',
        'archived' => 'Arsip',
    ];

    public function mount(): void
    {
        $this->filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'all';
    }

    public function setFilter(string $filter): void
    {
        $this->filter = array_key_exists($filter, $this->filters) ? $filter : 'all';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function archive(string $publicId): void
    {
        $this->changeArchiveState($publicId, true);
    }

    public function unarchive(string $publicId): void
    {
        $this->changeArchiveState($publicId, false);
    }

    public function render()
    {
        $filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'all';
        $search = trim($this->search);
        $userId = auth()->id();

        $threads = ChatThread::query()
            ->with(['application.service', 'client', 'latestMessage.sender', 'participantStates' => fn ($states) => $states->where('user_id', $userId)])
            ->withCount(['messages as unread_client_messages_count' => fn (Builder $messages) => $messages->whereNull('read_at')->whereNull('deleted_at')->whereHas('sender', fn (Builder $senders) => $senders->where('role', 'CLIENT'))])
            ->when($filter === 'archived', fn (Builder $query) => $query->whereHas('participantStates', fn (Builder $states) => $states->where('user_id', $userId)->whereNotNull('archived_at')))
            ->when($filter !== 'archived', fn (Builder $query) => $query->whereDoesntHave('participantStates', fn (Builder $states) => $states->where('user_id', $userId)->whereNotNull('archived_at')))
            ->when($filter === 'unread', fn (Builder $query) => $query->whereHas('messages', fn (Builder $messages) => $messages->whereNull('read_at')->whereNull('deleted_at')->whereHas('sender', fn (Builder $senders) => $senders->where('role', 'CLIENT'))))
            ->when($filter === 'application', fn (Builder $query) => $query->where('context_type', 'APPLICATION'))
            ->when($filter === 'general', fn (Builder $query) => $query->where('context_type', 'GENERAL_SUPPORT'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $matching) use ($search): void {
                    $matching->whereHas('client', fn (Builder $clients) => $clients->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"))
                        ->orWhereHas('application', fn (Builder $applications) => $applications->whereLike('public_id', "%{$search}%")->orWhereHas('service', fn (Builder $services) => $services->whereLike('name', "%{$search}%")));
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('livewire.admin.support-inbox', compact('threads', 'filter', 'search'));
    }

    private function changeArchiveState(string $publicId, bool $archive): void
    {
        $thread = ChatThread::query()->where('public_id', $publicId)->firstOrFail();
        Gate::authorize('view', $thread);
        $this->resetErrorBag('archive');

        try {
            $service = app(ChatThreadArchiveService::class);
            $archive ? $service->archive($thread, auth()->user()) : $service->unarchive($thread, auth()->user());
        } catch (DomainException $exception) {
            $this->addError('archive', $exception->getMessage());

            return;
        }

        $this->resetPage();
    }
}
