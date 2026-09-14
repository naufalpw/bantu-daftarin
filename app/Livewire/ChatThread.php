<?php

namespace App\Livewire;

use App\Models\ChatThread as ChatThreadModel;
use App\Services\AuditService;
use App\Services\ChatMessageManagementService;
use App\Services\ChatThreadArchiveService;
use App\Services\ChatUnreadEmailService;
use App\Services\NotificationService;
use App\Support\ChatPresence;
use App\Support\ChatPresentation;
use App\Support\ChatQuickReplyPresenter;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ChatThread extends Component
{
    private const TYPING_TTL_SECONDS = 5;

    private const PRESENCE_REFRESH_SECONDS = 20;

    private const SEND_RATE_LIMIT = 30;

    private const SEND_RATE_DECAY_SECONDS = 60;

    private const TYPING_RATE_LIMIT = 60;

    private const TYPING_RATE_DECAY_SECONDS = 60;

    public string $threadId;

    public string $body = '';

    public string $quickReply = '';

    public ?int $editingMessageId = null;

    public string $editingBody = '';

    public ?int $deletingMessageId = null;

    public bool $isArchived = false;

    public bool $isOtherParticipantTyping = false;

    /** @var array{is_online:bool,label:string} */
    public array $counterpartPresence = ['is_online' => false, 'label' => 'Sedang tidak aktif'];

    public int $lastPresenceCheckedAt = 0;

    public function mount(string $threadId): void
    {
        $this->threadId = $threadId;
        $this->markRead();
    }

    public function send(): void
    {
        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);
        $thread = $this->thread();
        $userId = (int) auth()->id();
        $limiterKey = "chat:send:{$userId}:{$thread->getKey()}";

        if (RateLimiter::tooManyAttempts($limiterKey, self::SEND_RATE_LIMIT)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $this->addError('body', "Terlalu banyak pesan terkirim. Mohon tunggu {$seconds} detik.");

            return;
        }

        RateLimiter::hit($limiterKey, self::SEND_RATE_DECAY_SECONDS);

        [$message, $recipient] = DB::transaction(function () use ($thread): array {
            $lockedThread = ChatThreadModel::query()
                ->with(['client', 'assignedAdmin.user'])
                ->lockForUpdate()
                ->findOrFail($thread->getKey());
            $this->assertThreadAccess($lockedThread);
            $this->assignCurrentAdmin($lockedThread);
            $recipient = auth()->user()->isAdmin() ? $lockedThread->client : $lockedThread->assignedAdmin?->user;
            $message = $lockedThread->messages()->create(['sender_user_id' => auth()->id(), 'body' => $this->body]);
            $lockedThread->forceFill(['last_message_at' => now()])->save();
            app(ChatThreadArchiveService::class)->unarchiveForNewMessage($lockedThread, auth()->user());

            if ($recipient) {
                app(ChatUnreadEmailService::class)->schedule($lockedThread, $recipient);
            }

            return [$message, $recipient];
        });

        if ($recipient) {
            app(NotificationService::class)->database($recipient, 'chat.unread', ['thread_id' => $thread->public_id]);
        }
        app(AuditService::class)->record('chat.message_sent', $message, ['thread_id' => $thread->public_id], auth()->user());
        $this->body = '';
        $this->isArchived = false;
        Cache::forget($this->typingCacheKey($thread, (int) auth()->id()));
        $this->syncTypingState($thread);
        $this->dispatch('chat-message-sent');
    }

    public function typing(): void
    {
        $thread = $this->thread();
        $userId = (int) auth()->id();
        $key = $this->typingCacheKey($thread, $userId);

        if (blank(trim($this->body))) {
            Cache::forget($key);
            $this->syncTypingState($thread);

            return;
        }

        $typingLimiterKey = "chat:typing:{$userId}:{$thread->getKey()}";
        if (! RateLimiter::tooManyAttempts($typingLimiterKey, self::TYPING_RATE_LIMIT)) {
            RateLimiter::hit($typingLimiterKey, self::TYPING_RATE_DECAY_SECONDS);
            Cache::put($key, true, now()->addSeconds(self::TYPING_TTL_SECONDS));
        }

        $this->syncTypingState($thread);
    }

    public function updatedBody(): void
    {
        $this->typing();
    }

    public function updatedQuickReply(string $key): void
    {
        if (blank($key) || ! auth()->user()?->isAdmin()) {
            $this->quickReply = '';

            return;
        }

        $template = ChatQuickReplyPresenter::forThread($this->thread())[$key] ?? null;
        $this->quickReply = '';

        if ($template === null) {
            return;
        }

        $this->body = blank(trim($this->body))
            ? $template
            : rtrim($this->body)."\n\n".$template;
        $this->typing();
    }

    public function markRead(): void
    {
        $thread = $this->thread();
        $readMessageCount = $thread->messages()
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->where('sender_user_id', '!=', auth()->id())
            ->update(['read_at' => now(), 'read_by_user_id' => auth()->id()]);

        if ($readMessageCount > 0) {
            $this->dispatch('chat-unread-updated');
        }

        $this->syncTypingState($thread);
        $this->syncCounterpartPresence($thread, true);
    }

    public function refreshPresence(): void
    {
        $this->syncCounterpartPresence($this->thread(), true);
    }

    public function startEditing(int $messageId): void
    {
        $message = $this->manageableMessage($messageId);
        $this->editingMessageId = $message->getKey();
        $this->editingBody = $message->body;
        $this->deletingMessageId = null;
        $this->resetErrorBag('editingBody');
    }

    public function cancelEditing(): void
    {
        $this->editingMessageId = null;
        $this->editingBody = '';
        $this->resetErrorBag('editingBody');
    }

    public function saveEdit(): void
    {
        if ($this->editingMessageId === null) {
            return;
        }

        $this->editingBody = trim($this->editingBody);
        $this->validate(['editingBody' => ['required', 'string', 'max:2000']]);

        try {
            app(ChatMessageManagementService::class)->edit($this->thread(), $this->editingMessageId, auth()->user(), $this->editingBody);
        } catch (DomainException $exception) {
            $this->addError('editingBody', $exception->getMessage());

            return;
        }

        $this->cancelEditing();
    }

    public function confirmDelete(int $messageId): void
    {
        $this->manageableMessage($messageId);
        $this->deletingMessageId = $messageId;
        $this->cancelEditing();
    }

    public function cancelDelete(): void
    {
        $this->deletingMessageId = null;
    }

    public function deleteMessage(): void
    {
        if ($this->deletingMessageId === null) {
            return;
        }

        try {
            app(ChatMessageManagementService::class)->delete($this->thread(), $this->deletingMessageId, auth()->user());
        } catch (DomainException $exception) {
            $this->addError('messageAction', $exception->getMessage());

            return;
        }

        $this->deletingMessageId = null;
        $this->dispatch('chat-unread-updated');
    }

    public function archiveConversation(): void
    {
        try {
            app(ChatThreadArchiveService::class)->archive($this->thread(), auth()->user());
            $this->isArchived = true;
        } catch (DomainException $exception) {
            $this->addError('archive', $exception->getMessage());
        }
    }

    public function unarchiveConversation(): void
    {
        app(ChatThreadArchiveService::class)->unarchive($this->thread(), auth()->user());
        $this->isArchived = false;
    }

    public function render()
    {
        $thread = $this->thread();
        $this->syncTypingState($thread);
        $this->syncCounterpartPresence($thread);

        $thread->load(['messages.sender', 'client', 'assignedAdmin.user', 'application.service']);
        $this->isArchived = $thread->participantStates()
            ->where('user_id', auth()->id())
            ->whereNotNull('archived_at')
            ->exists();

        return view('livewire.chat-thread', [
            'thread' => $thread,
            'messageGroups' => ChatPresentation::groupedMessages($thread->messages),
            'quickReplies' => auth()->user()->isAdmin() ? ChatQuickReplyPresenter::forThread($thread) : [],
            'quickReplyLabels' => ChatQuickReplyPresenter::labels(),
        ]);
    }

    private function thread(): ChatThreadModel
    {
        $thread = ChatThreadModel::where('public_id', $this->threadId)->firstOrFail();
        $this->assertThreadAccess($thread);

        return $thread;
    }

    private function manageableMessage(int $messageId)
    {
        $message = $this->thread()->messages()->whereKey($messageId)->firstOrFail();

        abort_unless($message->sender_user_id === auth()->id(), 403);

        if (! $message->canBeManagedBy(auth()->user())) {
            throw new DomainException('Pesan sudah dibaca, kedaluwarsa, atau tidak dapat dikelola lagi.');
        }

        return $message;
    }

    private function assertThreadAccess(ChatThreadModel $thread): void
    {
        abort_unless(auth()->user() && (auth()->user()->isAdmin() || $thread->client_user_id === auth()->id()), 403);
    }

    private function syncTypingState(ChatThreadModel $thread): void
    {
        $otherParticipantId = auth()->user()->isAdmin()
            ? $thread->client_user_id
            : $thread->assignedAdmin?->user_id;

        $this->isOtherParticipantTyping = $otherParticipantId !== null
            && Cache::has($this->typingCacheKey($thread, (int) $otherParticipantId));
    }

    private function syncCounterpartPresence(ChatThreadModel $thread, bool $force = false): void
    {
        $checkedAt = now()->timestamp;
        if (! $force && $this->lastPresenceCheckedAt > 0 && $checkedAt - $this->lastPresenceCheckedAt < self::PRESENCE_REFRESH_SECONDS) {
            return;
        }

        $presence = app(ChatPresence::class);
        $this->counterpartPresence = auth()->user()->isAdmin()
            ? $presence->forUser($thread->client)
            : $presence->forSupportTeam();
        $this->lastPresenceCheckedAt = $checkedAt;
    }

    private function typingCacheKey(ChatThreadModel $thread, int $userId): string
    {
        return 'chat-typing:'.$thread->public_id.':'.$userId;
    }

    private function assignCurrentAdmin(ChatThreadModel $thread): void
    {
        if (! auth()->user()->isAdmin() || $thread->assigned_admin_id !== null) {
            return;
        }

        $admin = auth()->user()->admin;
        if ($admin?->is_active) {
            $thread->forceFill(['assigned_admin_id' => $admin->getKey()])->save();
            $thread->setRelation('assignedAdmin', $admin);
        }
    }
}
