<?php

namespace App\Http\Controllers;

use App\Models\ChatThread;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function show(string $publicId): View
    {
        $thread = ChatThread::where('public_id', $publicId)->firstOrFail();
        $this->authorize('view', $thread);
        request()->attributes->set('is_general_support_chat', $thread->isGeneralSupport());

        return view('chat.show', ['thread' => $thread->load(['application.service', 'client', 'assignedAdmin.user'])]);
    }
}
