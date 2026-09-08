<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadUserState;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ChatThreadArchiveService
{
    public function archive(ChatThread $thread, User $actor): void
    {
        DB::transaction(function () use ($thread, $actor): void {
            $lockedThread = $this->authorizedThread($thread, $actor);

            if ($this->hasUnreadIncomingMessages($lockedThread, $actor)) {
                throw new DomainException('Baca pesan yang belum dibaca sebelum mengarsipkan percakapan.');
            }

            ChatThreadUserState::query()->updateOrCreate(
                ['chat_thread_id' => $lockedThread->getKey(), 'user_id' => $actor->getKey()],
                ['archived_at' => now()],
            );

            app(AuditService::class)->record('chat.thread_archived', $lockedThread, [], $actor);
        });
    }

    public function unarchive(ChatThread $thread, User $actor): void
    {
        DB::transaction(function () use ($thread, $actor): void {
            $lockedThread = $this->authorizedThread($thread, $actor);

            ChatThreadUserState::query()
                ->where('chat_thread_id', $lockedThread->getKey())
                ->where('user_id', $actor->getKey())
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null, 'updated_at' => now()]);

            app(AuditService::class)->record('chat.thread_unarchived', $lockedThread, [], $actor);
        });
    }

    public function unarchiveForNewMessage(ChatThread $thread, User $sender): void
    {
        $recipientIds = $sender->isAdmin()
            ? [$thread->client_user_id]
            : User::query()
                ->where('role', UserRole::SUPER_ADMIN->value)
                ->where('is_active', true)
                ->whereHas('admin', fn ($admins) => $admins->where('is_active', true))
                ->pluck('id')
                ->all();

        $recipientIds[] = $sender->getKey();

        ChatThreadUserState::query()
            ->where('chat_thread_id', $thread->getKey())
            ->whereIn('user_id', array_unique($recipientIds))
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null, 'updated_at' => now()]);
    }

    private function authorizedThread(ChatThread $thread, User $actor): ChatThread
    {
        $lockedThread = ChatThread::query()->lockForUpdate()->findOrFail($thread->getKey());

        if (! $actor->isAdmin() && $lockedThread->client_user_id !== $actor->getKey()) {
            throw new AuthorizationException;
        }

        return $lockedThread;
    }

    private function hasUnreadIncomingMessages(ChatThread $thread, User $actor): bool
    {
        return ChatMessage::query()
            ->where('chat_thread_id', $thread->getKey())
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->where('sender_user_id', '!=', $actor->getKey())
            ->exists();
    }
}
