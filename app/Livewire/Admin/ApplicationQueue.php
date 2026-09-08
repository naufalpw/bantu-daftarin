<?php

namespace App\Livewire\Admin;

use App\Models\Application;
use App\Support\AdminApplicationPresenter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ApplicationQueue extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'filter', history: true, except: 'all')]
    public string $filter = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->filter = array_key_exists($this->filter, AdminApplicationPresenter::filters()) ? $this->filter : 'all';
    }

    public function setFilter(string $filter): void
    {
        $this->filter = array_key_exists($filter, AdminApplicationPresenter::filters()) ? $filter : 'all';
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
        $filters = AdminApplicationPresenter::filters();
        $filter = array_key_exists($this->filter, $filters) ? $this->filter : 'all';
        $search = trim($this->search);

        $applications = Application::query()
            ->with(['user', 'service'])
            ->when($filter !== 'all', fn (Builder $query) => $query->whereIn('status', AdminApplicationPresenter::statusesFor($filter)))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $matching) use ($search): void {
                    $matching->whereLike('public_id', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $users) => $users->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"))
                        ->orWhereHas('service', fn (Builder $services) => $services->whereLike('name', "%{$search}%"));
                });
            })
            ->orderByRaw("case
                when status in ('DOCUMENTS_SUBMITTED', 'REVISION_SUBMITTED', 'UNDER_REVIEW', 'RESULT_REVIEW') then 1
                when status in ('DOCUMENTS_ACCEPTED', 'ESTIMATE_PENDING', 'IN_PROGRESS', 'WAITING_EXTERNAL_PROCESS', 'RESULT_UPLOADED') then 2
                when status in ('COMPLETED', 'ARCHIVED') then 4
                when status = 'CANCELLED' then 5
                else 3 end")
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('livewire.admin.application-queue', compact('applications', 'filter', 'filters', 'search'));
    }
}
