<?php

namespace App\Livewire;

use App\Models\ChatThread as ChatThreadModel;
use App\Notifications\ChatUnreadNotification;
use App\Services\AuditService;
use App\Services\NotificationService;
use Livewire\Component;

class ChatThread extends Component
{
    public string $threadId;

    public string $body = '';

    public function mount(string $threadId): void
    {
        $this->threadId = $threadId;
        $this->thread();
    }

    public function send(): void
    {
        $this->validate(['body' => ['required', 'string', 'max:2000']]);
        $thread = $this->thread();
        $recipient = auth()->user()->isAdmin() ? $thread->client : $thread->assignedAdmin?->user;
        $message = $thread->messages()->create(['sender_user_id' => auth()->id(), 'body' => $this->body]);
        $thread->forceFill(['last_message_at' => now()])->save();
        if ($recipient) {
            app(NotificationService::class)->database($recipient, 'chat.unread', ['thread_id' => $thread->public_id]);
            $recipient->notify(new ChatUnreadNotification($thread->public_id));
        }
        app(AuditService::class)->record('chat.message_sent', $message, ['thread_id' => $thread->public_id], auth()->user());
        $this->body = '';
    }

    public function markRead(): void
    {
        $thread = $this->thread();
        $thread->messages()->whereNull('read_at')->where('sender_user_id', '!=', auth()->id())->update(['read_at' => now(), 'read_by_user_id' => auth()->id()]);
    }

    public function render()
    {
        return view('livewire.chat-thread', ['thread' => $this->thread()->load('messages.sender')]);
    }

    private function thread(): ChatThreadModel
    {
        $thread = ChatThreadModel::where('public_id', $this->threadId)->firstOrFail();
        abort_unless(auth()->user() && (auth()->user()->isAdmin() || $thread->client_user_id === auth()->id()), 403);

        return $thread;
    }
}
