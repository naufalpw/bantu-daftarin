<?php

namespace App\Models;

use App\Enums\ChatThreadType;
use App\Models\Concerns\HasPublicId;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatThread extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return [
            'context_type' => ChatThreadType::class,
            'last_message_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatThread $thread): void {
            $thread->context_type ??= ChatThreadType::APPLICATION;
        });

        static::saving(function (ChatThread $thread): void {
            $type = $thread->context_type instanceof ChatThreadType
                ? $thread->context_type
                : ChatThreadType::tryFrom((string) $thread->context_type);

            if ($type === ChatThreadType::APPLICATION && $thread->application_id === null) {
                throw new DomainException('Application support threads require an application context.');
            }

            if ($type === ChatThreadType::GENERAL_SUPPORT && $thread->application_id !== null) {
                throw new DomainException('General support threads cannot use an application context.');
            }
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function isGeneralSupport(): bool
    {
        return $this->context_type === ChatThreadType::GENERAL_SUPPORT;
    }
}
