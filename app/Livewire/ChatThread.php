<?php

namespace App\Livewire;

use App\Models\ChatThread as ChatThreadModel;
use App\Notifications\ChatUnreadNotification;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Support\ChatPresentation;
use App\Support\ChatQuickReplyPresenter;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ChatThread extends Component
{
    private const TYPING_TTL_SECONDS = 5;

    public string $threadId;

    public string $body = '';

    public string $quickReply = '';

    public bool $isOtherParticipantTyping = false;

    public function mount(string $threadId): void
    {
        $this->threadId = $threadId;
        $this->thread();
    }

    public function send(): void
    {
        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);
        $thread = $this->thread();
        $this->assignCurrentAdmin($thread);
        $recipient = auth()->user()->isAdmin() ? $thread->client : $thread->assignedAdmin?->user;
        $message = $thread->messages()->create(['sender_user_id' => auth()->id(), 'body' => $this->body]);
        $thread->forceFill(['last_message_at' => now()])->save();
        if ($recipient) {
            app(NotificationService::class)->database($recipient, 'chat.unread', ['thread_id' => $thread->public_id]);
            $recipient->notify(new ChatUnreadNotification($thread->public_id));
        }
        app(AuditService::class)->record('chat.message_sent', $message, ['thread_id' => $thread->public_id], auth()->user());
        $this->body = '';
        Cache::forget($this->typingCacheKey($thread, (int) auth()->id()));
        $this->syncTypingState($thread);
    }

    public function typing(): void
    {
        $thread = $this->thread();
        $key = $this->typingCacheKey($thread, (int) auth()->id());

        if (blank(trim($this->body))) {
            Cache::forget($key);
        } else {
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
        $thread->messages()->whereNull('read_at')->where('sender_user_id', '!=', auth()->id())->update(['read_at' => now(), 'read_by_user_id' => auth()->id()]);
        $this->syncTypingState($thread);
    }

    public function render()
    {
        $thread = $this->thread();
        $this->syncTypingState($thread);

        $thread->load(['messages.sender', 'client', 'assignedAdmin.user', 'application.service']);

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
        abort_unless(auth()->user() && (auth()->user()->isAdmin() || $thread->client_user_id === auth()->id()), 403);

        return $thread;
    }

    private function syncTypingState(ChatThreadModel $thread): void
    {
        $otherParticipantId = auth()->user()->isAdmin()
            ? $thread->client_user_id
            : $thread->assignedAdmin?->user_id;

        $this->isOtherParticipantTyping = $otherParticipantId !== null
            && Cache::has($this->typingCacheKey($thread, (int) $otherParticipantId));
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
