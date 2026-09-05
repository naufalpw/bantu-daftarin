<?php

namespace App\Http\Controllers\Client;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\View\View;

class ServiceCatalogController extends Controller
{
    public function index(): View
    {
        return view('client.services.index', [
            'services' => Service::query()
                ->with([
                    'requirements' => fn ($query) => $query->where('active', true),
                    'applications' => fn ($query) => $query
                        ->where('user_id', request()->user()->getKey())
                        ->whereNotIn('status', [ApplicationStatus::COMPLETED->value, ApplicationStatus::ARCHIVED->value])
                        ->latest('updated_at'),
                ])
                ->whereIn('status', ['ACTIVE', 'COMING_SOON'])
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
