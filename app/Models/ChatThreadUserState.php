<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatThreadUserState extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'chat_email_pending_at' => 'datetime',
            'chat_email_last_sent_at' => 'datetime',
        ];
    }

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
