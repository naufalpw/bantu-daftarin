<?php

namespace App\Livewire\Admin;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class DocumentQueue extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'filter', history: true, except: 'review')]
    public string $filter = 'review';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, string> */
    public array $filters = [
        'all' => 'Semua',
        'review' => 'Menunggu pemeriksaan',
        'revision' => 'Revisi masuk',
        'needs_fix' => 'Perlu diperbaiki',
        'reviewed' => 'Pemeriksaan selesai',
    ];

    public function mount(): void
    {
        $this->filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'review';
    }

    public function setFilter(string $filter): void
    {
        $this->filter = array_key_exists($filter, $this->filters) ? $filter : 'review';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'review';
        $search = trim($this->search);
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
                    $matching->whereLike('public_id', "%{$search}%")
                        ->orWhereHas('requirements', fn (Builder $requirements) => $requirements->whereLike('name', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $users) => $users->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"))
                        ->orWhereHas('service', fn (Builder $services) => $services->whereLike('name', "%{$search}%"));
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

        return view('livewire.admin.document-queue', compact('applications', 'filter', 'search'));
    }
}
