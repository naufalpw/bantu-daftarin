<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentQueueController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter', 'review')->toString();
        $filter = in_array($filter, ['all', 'review', 'revision', 'needs_fix', 'reviewed'], true) ? $filter : 'review';
        $search = trim($request->string('q')->toString());

        $queueStatuses = [
            ApplicationStatus::DOCUMENTS_SUBMITTED->value,
            ApplicationStatus::UNDER_REVIEW->value,
            ApplicationStatus::REVISION_REQUIRED->value,
            ApplicationStatus::REVISION_SUBMITTED->value,
            ApplicationStatus::DOCUMENTS_ACCEPTED->value,
            ApplicationStatus::ESTIMATE_PENDING->value,
            ApplicationStatus::IN_PROGRESS->value,
            ApplicationStatus::WAITING_EXTERNAL_PROCESS->value,
            ApplicationStatus::RESULT_UPLOADED->value,
            ApplicationStatus::RESULT_REVIEW->value,
            ApplicationStatus::COMPLETED->value,
            ApplicationStatus::ARCHIVED->value,
        ];

        $applications = Application::query()
            ->whereIn('status', $queueStatuses)
            ->with(['user', 'service'])
            ->withCount([
                'requirements as document_requirements_count' => fn (Builder $query) => $query->where('active', true),
                'requirements as available_documents_count' => fn (Builder $query) => $query->where('active', true)->whereHas('documents', fn (Builder $documents) => $documents->where('active', true)->where('scan_status', 'PASSED')),
                'documents as pending_review_count' => fn (Builder $query) => $query->where('active', true)->where('review_status', 'PENDING'),
                'documents as revision_required_count' => fn (Builder $query) => $query->where('active', true)->whereIn('review_status', ['REVISION_REQUIRED', 'REJECTED']),
            ])
            ->withMax(['documents as latest_document_uploaded_at' => fn (Builder $query) => $query->where('active', true)], 'uploaded_at')
            ->withCasts(['latest_document_uploaded_at' => 'datetime'])
            ->when($filter === 'review', fn (Builder $query) => $query->whereIn('status', [ApplicationStatus::DOCUMENTS_SUBMITTED->value, ApplicationStatus::UNDER_REVIEW->value])->whereHas('documents', fn (Builder $documents) => $documents->where('active', true)->where('review_status', 'PENDING')))
            ->when($filter === 'revision', fn (Builder $query) => $query->where('status', ApplicationStatus::REVISION_SUBMITTED->value))
            ->when($filter === 'needs_fix', fn (Builder $query) => $query->where('status', ApplicationStatus::REVISION_REQUIRED->value))
            ->when($filter === 'reviewed', fn (Builder $query) => $query->whereIn('status', [ApplicationStatus::DOCUMENTS_ACCEPTED->value, ApplicationStatus::ESTIMATE_PENDING->value, ApplicationStatus::IN_PROGRESS->value, ApplicationStatus::WAITING_EXTERNAL_PROCESS->value, ApplicationStatus::RESULT_UPLOADED->value, ApplicationStatus::RESULT_REVIEW->value, ApplicationStatus::COMPLETED->value, ApplicationStatus::ARCHIVED->value]))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $matching) use ($search): void {
                    $matching->where('public_id', 'like', "%{$search}%")
                        ->orWhereHas('requirements', fn (Builder $requirements) => $requirements->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn (Builder $services) => $services->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("case
                when status in ('DOCUMENTS_SUBMITTED', 'UNDER_REVIEW') then 0
                when status = 'REVISION_SUBMITTED' then 1
                when status = 'REVISION_REQUIRED' then 2
                else 3 end")
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.documents.index', compact('applications', 'filter', 'search'));
    }
}
