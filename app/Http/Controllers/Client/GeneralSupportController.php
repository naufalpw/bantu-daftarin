<?php

namespace App\Http\Controllers\Client;

use App\Enums\ChatThreadType;
use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class GeneralSupportController extends Controller
{
    public function store(): RedirectResponse
    {
        $thread = DB::transaction(function (): ChatThread {
            $client = User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();

            return ChatThread::query()->firstOrCreate([
                'client_user_id' => $client->getKey(),
                'context_type' => ChatThreadType::GENERAL_SUPPORT,
            ], [
                'application_id' => null,
            ]);
        });

        return redirect()->route('client.chat.show', $thread->public_id);
    }
}
