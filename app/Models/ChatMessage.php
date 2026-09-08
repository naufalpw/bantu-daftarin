<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class ChatMessage extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
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

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function canBeManagedBy(User $user): bool
    {
        return $this->sender_user_id === $user->getKey()
            && $this->read_at === null
            && $this->deleted_at === null
            && $this->created_at instanceof Carbon
            && $this->created_at->greaterThanOrEqualTo(now()->subMinutes(10));
    }

    public function displayBody(): string
    {
        return $this->deleted_at === null ? $this->body : 'Pesan ini telah dihapus';
    }
}
