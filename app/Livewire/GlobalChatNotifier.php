<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalChatNotifier extends Component
{
    private const MAX_VISIBLE_TOASTS = 3;

    public string $activeThreadId = '';

    public int $lastObservedMessageId = 0;

    public int $unreadThreadCount = 0;

    /**
     * @var array<string, array{thread_id:string,title:string,context:string,preview:string,count:int,message_ids:list<int>,href:string,action_label:string}>
     */
    public array $toasts = [];

    public function mount(?string $activeThreadId = null): void
    {
        $this->activeThreadId = $activeThreadId ?? $this->activeThreadIdFromRequest();
        $this->lastObservedMessageId = (int) ChatMessage::query()->max('id');
        $this->refreshUnreadThreadCount();
    }

    public function poll(): void
    {
        $latestMessageId = (int) ChatMessage::query()->max('id');

        if ($latestMessageId > $this->lastObservedMessageId) {
            $this->incomingUnreadMessages()
                ->where('id', '>', $this->lastObservedMessageId)
                ->orderBy('id')
                ->get()
                ->each(function (ChatMessage $message): void {
                    if ($message->thread?->public_id !== $this->activeThreadId) {
                        $this->addOrUpdateToast($message);
                    }
                });

            $this->lastObservedMessageId = $latestMessageId;
        }

        $this->reconcileToasts();
        $this->refreshUnreadThreadCount();
    }

    public function dismissToast(string $threadId): void
    {
        unset($this->toasts[$threadId]);
    }

    #[On('chat-unread-updated')]
    public function refreshUnreadCounter(): void
    {
        $this->refreshUnreadThreadCount();
    }

    public function render()
    {
        return view('livewire.global-chat-notifier', [
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
            'hasActiveChat' => $this->activeThreadId !== '',
        ]);
    }

    private function refreshUnreadThreadCount(): void
    {
        if (! auth()->check()) {
            $this->unreadThreadCount = 0;

            return;
        }

        $this->unreadThreadCount = $this->incomingUnreadMessages()
            ->distinct('chat_thread_id')
            ->count('chat_thread_id');
    }

    /**
     * @return Builder<ChatMessage>
     */
    private function incomingUnreadMessages(): Builder
    {
        $user = auth()->user();

        return ChatMessage::query()
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->where('sender_user_id', '!=', $user->getKey())
            ->with(['thread.application.service', 'thread.client'])
            ->when(
                $user->isAdmin(),
                fn (Builder $messages) => $messages->whereHas('sender', fn (Builder $senders) => $senders->where('role', UserRole::CLIENT->value)),
                fn (Builder $messages) => $messages->whereHas('thread', fn (Builder $threads) => $threads->where('client_user_id', $user->getKey())),
            );
    }

    private function reconcileToasts(): void
    {
        if ($this->toasts === []) {
            return;
        }

        foreach ($this->toasts as $threadId => $toast) {
            $messages = $this->incomingUnreadMessages()
                ->whereHas('thread', fn (Builder $threads) => $threads->where('public_id', $threadId))
                ->whereIn('id', $toast['message_ids'] ?? [])
                ->orderBy('id')
                ->get();

            if ($messages->isEmpty()) {
                unset($this->toasts[$threadId]);

                continue;
            }

            $latest = $messages->last();
            $this->toasts[$threadId] = [
                ...$toast,
                'count' => $messages->count(),
                'message_ids' => $messages->pluck('id')->all(),
                'preview' => Str::limit((string) preg_replace('/\s+/', ' ', trim($latest->body)), 100),
            ];
        }
    }

    private function addOrUpdateToast(ChatMessage $message): void
    {
        $thread = $message->thread;

        if (! $thread instanceof ChatThread) {
            return;
        }

        $threadId = (string) $thread->public_id;
        $existing = $this->toasts[$threadId] ?? null;
        unset($this->toasts[$threadId]);
        $messageIds = array_values(array_unique([
            ...($existing['message_ids'] ?? []),
            $message->getKey(),
        ]));

        $this->toasts[$threadId] = [
            'thread_id' => $threadId,
            'title' => auth()->user()->isAdmin()
                ? 'Pesan baru dari '.($thread->client?->name ?? 'klien')
                : 'Tim Bantu Daftarin',
            'context' => $this->threadContext($thread),
            'preview' => Str::limit((string) preg_replace('/\s+/', ' ', trim($message->body)), 100),
            'count' => count($messageIds),
            'message_ids' => $messageIds,
            'href' => auth()->user()->isAdmin()
                ? route('admin.chat.show', $threadId)
                : route('client.chat.show', $threadId),
            'action_label' => 'Buka percakapan',
        ];

        while (count($this->toasts) > self::MAX_VISIBLE_TOASTS) {
            array_shift($this->toasts);
        }
    }

    private function threadContext(ChatThread $thread): string
    {
        if ($thread->isGeneralSupport()) {
            return 'Bantuan Umum';
        }

        $service = $thread->application?->service?->name ?? 'Pengajuan';
        $publicId = strtoupper(substr((string) $thread->application?->public_id, -6));

        return $publicId === '' ? $service : $service.' · ID …'.$publicId;
    }

    private function activeThreadIdFromRequest(): string
    {
        if (! request()->routeIs('admin.chat.show', 'client.chat.show')) {
            return '';
        }

        return (string) request()->route('publicId');
    }
}
