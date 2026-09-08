<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class UserDirectory extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'filter', history: true, except: 'all')]
    public string $filter = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, string> */
    public array $filters = ['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Tidak aktif'];

    public function mount(): void
    {
        $this->filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'all';
    }

    public function setFilter(string $filter): void
    {
        $this->filter = array_key_exists($filter, $this->filters) ? $filter : 'all';
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
        $filter = array_key_exists($this->filter, $this->filters) ? $this->filter : 'all';
        $search = trim($this->search);

        $users = User::query()
            ->where('role', UserRole::CLIENT->value)
            ->withCount('applications')
            ->when($filter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $matching) => $matching->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('livewire.admin.user-directory', compact('users', 'filter', 'search'));
    }
}
