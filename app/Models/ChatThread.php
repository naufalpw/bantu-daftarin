<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatThread extends BaseModel
{
    use HasFactory, HasPublicId;

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
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
}
