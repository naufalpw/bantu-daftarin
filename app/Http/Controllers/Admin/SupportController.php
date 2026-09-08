<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Services\ChatThreadArchiveService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(): View
    {
        return view('admin.support.index');
    }

    public function archive(string $publicId, ChatThreadArchiveService $archive): RedirectResponse
    {
        $thread = ChatThread::query()->where('public_id', $publicId)->firstOrFail();
        $this->authorize('view', $thread);

        try {
            $archive->archive($thread, request()->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['archive' => $exception->getMessage()]);
        }

        return back()->with('status', 'Percakapan diarsipkan.');
    }

    public function unarchive(string $publicId, ChatThreadArchiveService $archive): RedirectResponse
    {
        $thread = ChatThread::query()->where('public_id', $publicId)->firstOrFail();
        $this->authorize('view', $thread);
        $archive->unarchive($thread, request()->user());

        return back()->with('status', 'Percakapan dikeluarkan dari arsip.');
    }
}
