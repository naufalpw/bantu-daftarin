<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(): View
    {
        $threads = ChatThread::query()
            ->with(['application.service', 'client', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('admin.support.index', compact('threads'));
    }
}
