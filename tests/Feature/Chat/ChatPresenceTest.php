<?php

namespace Tests\Feature\Chat;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Middleware\ReadOnlySession;
use App\Livewire\ChatThread as ChatThreadComponent;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ChatThread;
use App\Models\Service;
use App\Models\User;
use App\Support\ChatPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ChatPresenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->clear();
    }

    public function test_testing_environment_uses_the_ephemeral_presence_store(): void
    {
        $this->assertSame('array', app(ChatPresence::class)->storeName());
    }

    public function test_authenticated_client_heartbeat_records_only_the_authenticated_user(): void
    {
        $client = User::factory()->create();
        $otherClient = User::factory()->create();
        $presence = app(ChatPresence::class);

        $this->actingAs($client)
            ->post(route('presence.heartbeat'), ['user_id' => $otherClient->id, 'role' => UserRole::SUPER_ADMIN->value])
            ->assertNoContent();

        $this->assertNotNull($presence->lastSeenAt($client));
        $this->assertNull($presence->lastSeenAt($otherClient));
    }

    public function test_authenticated_admin_heartbeat_requires_an_active_admin_profile(): void
    {
        $admin = $this->admin();
        $presence = app(ChatPresence::class);

        $this->actingAs($admin->user)
            ->post(route('presence.heartbeat'))
            ->assertNoContent();

        $this->assertNotNull($presence->lastSeenAt($admin->user));

        $adminWithoutProfile = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->actingAs($adminWithoutProfile)
            ->post(route('presence.heartbeat'))
            ->assertForbidden();
    }

    public function test_unauthenticated_request_does_not_create_presence(): void
    {
        $user = User::factory()->create();

        $this->post(route('presence.heartbeat'))->assertRedirect(route('login'));

        $this->assertNull(app(ChatPresence::class)->lastSeenAt($user));
    }

    public function test_presence_uses_recent_heartbeat_not_login_session_and_humanized_fallbacks(): void
    {
        $client = User::factory()->create();
        $presence = app(ChatPresence::class);

        $this->assertSame(['is_online' => false, 'label' => 'Sedang tidak aktif'], $presence->forUser($client));

        Cache::store('array')->put($presence->keyFor($client), now()->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));
        $this->assertSame(['is_online' => true, 'label' => 'Online'], $presence->forUser($client));

        Cache::store('array')->put($presence->keyFor($client), now()->subMinutes(5)->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));
        $this->assertSame(['is_online' => false, 'label' => 'Terakhir aktif beberapa menit lalu'], $presence->forUser($client));

        Cache::store('array')->forget($presence->keyFor($client));
        $this->assertSame(['is_online' => false, 'label' => 'Sedang tidak aktif'], $presence->forUser($client));
    }

    public function test_client_sees_team_presence_when_at_least_one_active_admin_is_recent(): void
    {
        $client = User::factory()->create(['name' => 'Klien Presence']);
        $onlineAdmin = $this->admin();
        $staleAdmin = $this->admin();
        $thread = $this->threadFor($client);
        $presence = app(ChatPresence::class);

        Cache::store('array')->put($presence->keyFor($onlineAdmin->user), now()->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));
        Cache::store('array')->put($presence->keyFor($staleAdmin->user), now()->subHours(3)->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));

        $component = Livewire::actingAs($client)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertSet('counterpartPresence.is_online', true)
            ->assertSee('Tim Bantu Daftarin')
            ->assertSee('Online')
            ->assertDontSee($onlineAdmin->user->name);

        Cache::store('array')->put($presence->keyFor($onlineAdmin->user), now()->subHours(2)->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));

        $component->call('refreshPresence')
            ->assertSet('counterpartPresence.is_online', false)
            ->assertSee('Terakhir aktif');
    }

    public function test_admin_sees_only_the_relevant_client_presence(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Klien Terkait']);
        $otherClient = User::factory()->create(['name' => 'Klien Lain']);
        $thread = $this->threadFor($client, $admin);
        $presence = app(ChatPresence::class);

        Cache::store('array')->put($presence->keyFor($client), now()->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));
        Cache::store('array')->put($presence->keyFor($otherClient), now()->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));

        $component = Livewire::actingAs($admin->user)
            ->test(ChatThreadComponent::class, ['threadId' => $thread->public_id])
            ->assertSet('counterpartPresence.is_online', true)
            ->assertSee('Klien Terkait')
            ->assertSee('Online')
            ->assertDontSee('Klien Lain');

        Cache::store('array')->put($presence->keyFor($client), now()->subMinutes(5)->timestamp, now()->addHours(ChatPresence::RETENTION_HOURS));

        $component->call('refreshPresence')
            ->assertSet('counterpartPresence.is_online', false)
            ->assertSee('Terakhir aktif beberapa menit lalu');
    }

    public function test_presence_heartbeat_is_mounted_only_in_authenticated_client_and_admin_layouts(): void
    {
        $client = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('data-presence-heartbeat', false)
            ->assertSee(route('presence.heartbeat'), false);

        $this->actingAs($admin->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-presence-heartbeat', false)
            ->assertSee(route('presence.heartbeat'), false);

        $this->get(route('home'))->assertDontSee('data-presence-heartbeat', false);
    }

    public function test_heartbeat_route_uses_a_read_only_authenticated_session_stack(): void
    {
        $route = app('router')->getRoutes()->getByName('presence.heartbeat');
        $middleware = app('router')->gatherRouteMiddleware($route);

        $this->assertContains(ReadOnlySession::class, $middleware);
        $this->assertSame(1, collect($middleware)->filter(fn (string $item): bool => $item === ReadOnlySession::class)->count());
        $this->assertNotContains(StartSession::class, $middleware);
    }

    private function admin(): Admin
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        return Admin::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    private function threadFor(User $client, ?Admin $admin = null): ChatThread
    {
        $application = Application::create([
            'user_id' => $client->id,
            'service_id' => Service::factory()->create()->id,
            'status' => ApplicationStatus::DRAFT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        return ChatThread::create([
            'application_id' => $application->id,
            'client_user_id' => $client->id,
            'assigned_admin_id' => $admin?->id,
        ]);
    }
}
