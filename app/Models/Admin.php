<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends BaseModel
{
    use HasFactory, HasPublicId;

    protected $fillable = ['user_id', 'email', 'role', 'is_active', 'last_login_at'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'assigned_admin_id');
    }
}
