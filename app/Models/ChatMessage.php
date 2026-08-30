<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function readBy()
    {
        return $this->belongsTo(User::class, 'read_by_user_id');
    }
}
