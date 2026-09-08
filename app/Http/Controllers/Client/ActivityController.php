<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;

class ActivityController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('client.applications.index');
    }

    public function show(string $publicId): RedirectResponse
    {
        $application = Application::query()->where('public_id', $publicId)->firstOrFail();
        $this->authorize('view', $application);

        return redirect()->to(route('client.applications.show', $application->public_id).'#proses');
    }
}
