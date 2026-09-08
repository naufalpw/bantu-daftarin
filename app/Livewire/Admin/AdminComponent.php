<?php

namespace App\Livewire\Admin;

use Livewire\Component;

abstract class AdminComponent extends Component
{
    public function boot(): void
    {
        $this->ensureAdmin();
    }

    public function initialize(): void
    {
        $this->ensureAdmin();
    }

    protected function ensureAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }
}
