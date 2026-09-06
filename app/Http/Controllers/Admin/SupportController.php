<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter', 'all')->toString();
        $filter = in_array($filter, ['all', 'unread', 'application', 'general'], true) ? $filter : 'all';
        $search = trim($request->string('q')->toString());

        $threads = ChatThread::query()
            ->with(['application.service', 'client', 'latestMessage.sender'])
            ->withCount(['messages as unread_client_messages_count' => fn (Builder $messages) => $messages->whereNull('read_at')->whereHas('sender', fn (Builder $senders) => $senders->where('role', 'CLIENT'))])
            ->when($filter === 'unread', fn (Builder $query) => $query->whereHas('messages', fn (Builder $messages) => $messages->whereNull('read_at')->whereHas('sender', fn (Builder $senders) => $senders->where('role', 'CLIENT'))))
            ->when($filter === 'application', fn (Builder $query) => $query->where('context_type', 'APPLICATION'))
            ->when($filter === 'general', fn (Builder $query) => $query->where('context_type', 'GENERAL_SUPPORT'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $matching) use ($search): void {
                    $matching->whereHas('client', fn (Builder $clients) => $clients->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('application', fn (Builder $applications) => $applications->where('public_id', 'like', "%{$search}%")->orWhereHas('service', fn (Builder $services) => $services->where('name', 'like', "%{$search}%")));
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support.index', compact('threads', 'filter', 'search'));
    }
}
