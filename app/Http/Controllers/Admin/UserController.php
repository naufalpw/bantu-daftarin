<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }

    public function show(string $publicId): View
    {
        $user = User::query()->where('public_id', $publicId)->where('role', UserRole::CLIENT->value)->firstOrFail();
        $applications = $user->applications()->with('service')->latest('updated_at')->paginate(10, ['*'], 'applications_page');

        return view('admin.users.show', compact('user', 'applications'));
    }
}
