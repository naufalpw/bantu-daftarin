<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\ChatThreadType;
use App\Enums\UserRole;
use App\Livewire\Admin\ActivityChart;
use App\Livewire\Admin\ActivityFeed;
use App\Livewire\Admin\ApplicationQueue;
use App\Livewire\Admin\DocumentQueue;
use App\Livewire\Admin\SupportInbox;
use App\Livewire\Admin\UserDirectory;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Support\AdminActivityPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminReactiveFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_filter_search_and_pagination_are_livewire_state(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Reactive']);
        $review = $this->application($client, ApplicationStatus::DOCUMENTS_SUBMITTED, 'NPWP Review Reactive');
        $completed = $this->application($client, ApplicationStatus::COMPLETED, 'NPWP Selesai Reactive');

        Livewire::withQueryParams(['filter' => 'review', 'q' => 'Reactive'])
            ->actingAs($admin->user)
            ->test(ApplicationQueue::class)
            ->assertSet('filter', 'review')
            ->assertSet('search', 'Reactive')
            ->assertSee($review->service->name)
            ->assertDontSee($completed->service->name)
            ->call('setPage', 2)
            ->call('setFilter', 'all')
            ->assertSet('paginators.page', 1)
            ->assertSee($completed->service->name)
            ->set('search', 'Klien')
            ->assertSet('paginators.page', 1);
    }

    public function test_document_activity_and_user_components_keep_url_state(): void
    {
        $admin = $this->admin();

        Livewire::withQueryParams(['filter' => 'needs_fix'])
            ->actingAs($admin->user)
            ->test(DocumentQueue::class)
            ->assertSet('filter', 'needs_fix')
            ->call('setFilter', 'review')
            ->assertSet('paginators.page', 1);

        Livewire::withQueryParams(['category' => 'document'])
            ->actingAs($admin->user)
            ->test(ActivityFeed::class)
            ->assertSet('category', 'document')
            ->call('setCategory', 'payment')
            ->assertSet('category', 'payment')
            ->assertSet('paginators.page', 1);

        Livewire::withQueryParams(['filter' => 'inactive', 'q' => 'client'])
            ->actingAs($admin->user)
            ->test(UserDirectory::class)
            ->assertSet('filter', 'inactive')
            ->assertSet('search', 'client')
            ->call('setFilter', 'active')
            ->assertSet('filter', 'active')
            ->assertSet('paginators.page', 1);

        Livewire::actingAs($admin->user)
            ->test(SupportInbox::class)
            ->call('setFilter', 'unread')
            ->assertSet('filter', 'unread')
            ->assertSet('paginators.page', 1);
    }

    public function test_dashboard_period_updates_only_chart_component_state(): void
    {
        $admin = $this->admin();
        $activity = AdminActivityPresenter::dashboard(30);

        Livewire::withQueryParams(['period' => 7])
            ->actingAs($admin->user)
            ->test(ActivityChart::class, ['period' => 7, 'periods' => [7 => '7 hari terakhir', 30 => '30 hari terakhir', 90 => '90 hari terakhir'], 'activity' => $activity])
            ->assertSet('period', 7)
            ->set('period', 90)
            ->assertSet('activity.days', 90);
    }

    public function test_support_archive_action_uses_existing_service_and_updates_component(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Arsip Reactive']);
        $thread = ChatThread::create(['client_user_id' => $client->id, 'context_type' => ChatThreadType::GENERAL_SUPPORT]);

        Livewire::actingAs($admin->user)
            ->test(SupportInbox::class)
            ->call('archive', $thread->public_id)
            ->assertDontSee('Klien Arsip Reactive');

        $this->assertDatabaseHas('chat_thread_user_states', [
            'chat_thread_id' => $thread->id,
            'user_id' => $admin->user->id,
        ]);
    }

    public function test_admin_pages_render_partial_update_controls_inside_the_existing_shell(): void
    {
        $admin = $this->admin();

        foreach (['admin.applications.index', 'admin.documents.index', 'admin.support.index', 'admin.users.index'] as $routeName) {
            $this->actingAs($admin->user)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('wire:model.live.debounce.350ms="search"', false)
                ->assertSee('type="button"', false)
                ->assertSee('wire:click="setFilter(', false)
                ->assertSee('aria-pressed=', false)
                ->assertDontSee('wire:click.prevent="setFilter(', false)
                ->assertSee('wire:loading.delay.flex', false)
                ->assertSee('wire:target=', false)
                ->assertSee('style="display: none"', false)
                ->assertSee('bd-admin-reactive-results', false)
                ->assertDontSee('wire:loading.delay.200ms', false)
                ->assertDontSee('wire:loading.class="bd-admin-reactive-region--loading"', false)
                ->assertDontSee('>Cari</button>', false);
        }

        $this->actingAs($admin->user)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('type="button"', false)
            ->assertSee('wire:click="setCategory(', false)
            ->assertSee('aria-pressed=', false)
            ->assertDontSee('wire:click.prevent="setCategory(', false);

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('wire:model.live="period"', false)
            ->assertSee('wire:loading.delay.flex', false)
            ->assertSee('wire:target="period"', false)
            ->assertSee('style="display: none"', false)
            ->assertSee('bd-admin-reactive-results--chart', false)
            ->assertDontSee('wire:loading.delay.200ms', false)
            ->assertDontSee('wire:loading.class="bd-admin-reactive-region--loading"', false);
    }

    public function test_non_admin_cannot_mount_admin_livewire_data_component(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ApplicationQueue::class)
            ->assertStatus(403);
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'name' => 'Admin Reactive']);

        return Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);
    }

    private function application(User $user, ApplicationStatus $status, string $serviceName): Application
    {
        $service = Service::factory()->create(['name' => $serviceName]);

        return Application::factory()->create(['user_id' => $user->id, 'service_id' => $service->id, 'status' => $status]);
    }
}
