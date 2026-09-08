<?php

namespace App\Jobs;

use App\Services\ChatUnreadEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatUnreadEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $threadId,
        public readonly int $recipientId,
        public readonly string $pendingToken,
    ) {
        $this->afterCommit();
    }

    public function handle(ChatUnreadEmailService $chatUnreadEmail): void
    {
        $chatUnreadEmail->deliver($this->threadId, $this->recipientId, $this->pendingToken);
    }
}
