<?php

namespace App\Livewire\Admin;

use App\Support\AdminActivityPresenter;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ActivityFeed extends AdminComponent
{
    use WithPagination;

    #[Url(as: 'category', history: true, except: 'all')]
    public string $category = 'all';

    /** @var array<string, string> */
    public array $categories = [];

    public function mount(): void
    {
        $this->categories = AdminActivityPresenter::categories();
        $this->category = array_key_exists($this->category, $this->categories) ? $this->category : 'all';
    }

    public function setCategory(string $category): void
    {
        $this->category = array_key_exists($category, $this->categories) ? $category : 'all';
        $this->resetPage();
    }

    public function render()
    {
        $category = array_key_exists($this->category, $this->categories) ? $this->category : 'all';
        $activities = AdminActivityPresenter::paginate($category);
        $activities->appends(['category' => $category]);

        return view('livewire.admin.activity-feed', compact('activities', 'category'));
    }
}
