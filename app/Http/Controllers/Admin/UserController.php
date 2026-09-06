<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter', 'all')->toString();
        $filter = in_array($filter, ['all', 'active', 'inactive'], true) ? $filter : 'all';
        $search = trim($request->string('q')->toString());

        $users = User::query()
            ->where('role', UserRole::CLIENT->value)
            ->withCount('applications')
            ->when($filter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $matching) => $matching->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filter', 'search'));
    }

    public function show(string $publicId): View
    {
        $user = User::query()->where('public_id', $publicId)->where('role', UserRole::CLIENT->value)->firstOrFail();
        $applications = $user->applications()->with('service')->latest('updated_at')->paginate(10, ['*'], 'applications_page');

        return view('admin.users.show', compact('user', 'applications'));
    }
}
