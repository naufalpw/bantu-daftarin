<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ChatMessageManagementService
{
    public function edit(ChatThread $thread, int $messageId, User $actor, string $body): ChatMessage
    {
        $body = trim($body);

        if ($body === '') {
            throw new DomainException('Pesan tidak boleh kosong.');
        }

        if (mb_strlen($body) > 2000) {
            throw new DomainException('Pesan tidak boleh lebih dari 2000 karakter.');
        }

        return DB::transaction(function () use ($thread, $messageId, $actor, $body): ChatMessage {
            $message = $this->manageableMessage($thread, $messageId, $actor);
            $message->forceFill(['body' => $body, 'edited_at' => now()])->save();
            app(AuditService::class)->record('chat.message_edited', $message, [], $actor);

            return $message;
        });
    }

    public function delete(ChatThread $thread, int $messageId, User $actor): ChatMessage
    {
        return DB::transaction(function () use ($thread, $messageId, $actor): ChatMessage {
            $message = $this->manageableMessage($thread, $messageId, $actor);
            $message->forceFill(['deleted_at' => now(), 'deleted_by_user_id' => $actor->getKey()])->save();
            app(AuditService::class)->record('chat.message_deleted', $message, [], $actor);

            return $message;
        });
    }

    private function manageableMessage(ChatThread $thread, int $messageId, User $actor): ChatMessage
    {
        $lockedThread = ChatThread::query()->lockForUpdate()->findOrFail($thread->getKey());

        if (! $actor->isAdmin() && $lockedThread->client_user_id !== $actor->getKey()) {
            throw new AuthorizationException;
        }

        $message = ChatMessage::query()
            ->where('chat_thread_id', $lockedThread->getKey())
            ->whereKey($messageId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($message->sender_user_id !== $actor->getKey()) {
            throw new AuthorizationException;
        }

        if (! $message->canBeManagedBy($actor)) {
            throw new DomainException('Pesan sudah dibaca, kedaluwarsa, atau tidak dapat dikelola lagi.');
        }

        return $message;
    }
}
