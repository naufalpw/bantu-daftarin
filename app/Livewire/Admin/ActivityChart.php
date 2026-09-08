<?php

namespace App\Livewire\Admin;

use App\Support\AdminActivityPresenter;
use Livewire\Attributes\Url;

class ActivityChart extends AdminComponent
{
    #[Url(as: 'period', history: true, except: 30)]
    public int $period = 30;

    /** @var array<int, string> */
    public array $periods = [7 => '7 hari terakhir', 30 => '30 hari terakhir', 90 => '90 hari terakhir'];

    /** @var array<string, mixed> */
    public array $activity = [];

    public function mount(?int $period = null, array $periods = [], array $activity = []): void
    {
        if ($period !== null) {
            $this->period = in_array($period, [7, 30, 90], true) ? $period : 30;
        }
        $this->periods = $periods !== [] ? $periods : $this->periods;
        $this->activity = $this->chartActivity($activity !== [] ? $activity : AdminActivityPresenter::dashboard($this->period));
    }

    public function updatedPeriod(): void
    {
        if (! in_array($this->period, [7, 30, 90], true)) {
            $this->period = 30;
        }

        $this->activity = $this->chartActivity(AdminActivityPresenter::dashboard($this->period));
    }

    public function render()
    {
        return view('livewire.admin.activity-chart');
    }

    /** @param array<string, mixed> $activity */
    private function chartActivity(array $activity): array
    {
        return [
            'days' => (int) ($activity['days'] ?? $this->period),
            'total' => (int) ($activity['total'] ?? 0),
            'points' => collect($activity['points'] ?? [])->map(fn (array $point): array => [
                'date' => $point['date'],
                'key' => (string) ($point['key'] ?? ''),
                'label' => (string) ($point['label'] ?? ''),
                'count' => (int) ($point['count'] ?? 0),
            ])->values(),
        ];
    }
}
