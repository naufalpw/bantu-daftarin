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

        return view('chat.show', ['thread' => $thread->load('application')]);
    }
}
