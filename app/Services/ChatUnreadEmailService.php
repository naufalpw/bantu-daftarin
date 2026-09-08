<?php

namespace App\Services;

use App\Jobs\SendChatUnreadEmail;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadUserState;
use App\Models\User;
use App\Notifications\ChatUnreadNotification;
use App\Support\TransactionalEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatUnreadEmailService
{
    public const COALESCING_DELAY_SECONDS = 60;

    /**
     * Schedule one delayed opportunity for a recipient/thread pair.
     *
     * ChatThread::send() holds the thread row lock while persisting a message,
     * so rapid messages for one thread cannot create competing pending markers.
     */
    public function schedule(ChatThread $thread, User $recipient): void
    {
        $state = ChatThreadUserState::query()
            ->where('chat_thread_id', $thread->getKey())
            ->where('user_id', $recipient->getKey())
            ->lockForUpdate()
            ->first();

        if ($state?->chat_email_pending_token !== null) {
            return;
        }

        $pendingToken = (string) Str::uuid();
        $state ??= new ChatThreadUserState([
            'chat_thread_id' => $thread->getKey(),
            'user_id' => $recipient->getKey(),
        ]);
        $state->forceFill([
            'chat_email_pending_token' => $pendingToken,
            'chat_email_pending_at' => now(),
        ])->save();

        SendChatUnreadEmail::dispatch(
            threadId: $thread->getKey(),
            recipientId: $recipient->getKey(),
            pendingToken: $pendingToken,
        )->delay(now()->addSeconds(self::COALESCING_DELAY_SECONDS))->afterCommit();
    }

    /**
     * Send only when canonical unread state still includes an incoming,
     * non-deleted message for the intended recipient.
     */
    public function deliver(int $threadId, int $recipientId, string $pendingToken): void
    {
        $delivery = DB::transaction(function () use ($threadId, $recipientId, $pendingToken): ?array {
            $thread = ChatThread::query()->lockForUpdate()->find($threadId);
            if ($thread === null) {
                return null;
            }

            $thread->loadMissing('application.service');

            $state = ChatThreadUserState::query()
                ->where('chat_thread_id', $thread->getKey())
                ->where('user_id', $recipientId)
                ->lockForUpdate()
                ->first();

            if ($state === null || $state->chat_email_pending_token !== $pendingToken) {
                return null;
            }

            $recipient = User::query()->find($recipientId);
            if ($recipient === null || ! $this->hasUnreadIncomingMessages($thread, $recipient)) {
                $state->forceFill([
                    'chat_email_pending_token' => null,
                    'chat_email_pending_at' => null,
                ])->save();

                return null;
            }

            $state->forceFill([
                'chat_email_pending_token' => null,
                'chat_email_pending_at' => null,
                'chat_email_last_sent_at' => now(),
            ])->save();

            return [$recipient, (string) $thread->public_id, $this->conversationContext($thread)];
        });

        if ($delivery !== null) {
            [$recipient, $threadPublicId, $conversationContext] = $delivery;
            $recipient->notify(new ChatUnreadNotification($threadPublicId, $conversationContext));
        }
    }

    private function hasUnreadIncomingMessages(ChatThread $thread, User $recipient): bool
    {
        return ChatMessage::query()
            ->where('chat_thread_id', $thread->getKey())
            ->where('sender_user_id', '!=', $recipient->getKey())
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function conversationContext(ChatThread $thread): string
    {
        if ($thread->isGeneralSupport()) {
            return 'Bantuan Umum';
        }

        $application = $thread->application;
        $serviceName = $application?->service?->name ?? 'Pengajuan';
        $applicationId = TransactionalEmail::shortApplicationId($application?->public_id);

        return $applicationId === null ? $serviceName : $serviceName.' · ID '.$applicationId;
    }
}
