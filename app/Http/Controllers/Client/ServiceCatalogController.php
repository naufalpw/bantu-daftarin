<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\View\View;

class ServiceCatalogController extends Controller
{
    public function index(): View
    {
        return view('client.services.index', [
            'services' => Service::query()->whereIn('status', ['ACTIVE', 'COMING_SOON'])->orderBy('sort_order')->get(),
        ]);
    }
}
