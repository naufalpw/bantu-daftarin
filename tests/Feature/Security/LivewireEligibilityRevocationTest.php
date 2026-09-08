<?php

namespace Tests\Feature\Security;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureClient;
use App\Models\Admin;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Tests\TestCase;

class LivewireEligibilityRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_eligibility_middleware_is_registered_for_livewire_updates(): void
    {
        $middleware = app(PersistentMiddleware::class)->getPersistentMiddleware();

        $this->assertContains(EnsureAdmin::class, $middleware);
        $this->assertContains(EnsureClient::class, $middleware);
        $this->assertContains(EnsureEmailIsVerified::class, $middleware);
    }

    public function test_inactive_admin_user_cannot_reuse_an_existing_component_snapshot(): void
    {
        $admin = $this->admin();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($admin->user)->get(route('admin.applications.index'))->assertOk()->getContent(),
            'admin.application-queue',
        );

        $admin->user->forceFill(['is_active' => false])->save();
        $this->actingAs($admin->user->fresh());

        $this->livewireUpdate($snapshot, 'setFilter', ['review'])->assertForbidden();
    }

    public function test_inactive_admin_profile_cannot_reuse_an_existing_component_snapshot(): void
    {
        $admin = $this->admin();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($admin->user)->get(route('admin.applications.index'))->assertOk()->getContent(),
            'admin.application-queue',
        );

        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin->user->fresh());

        $this->livewireUpdate($snapshot, 'setFilter', ['review'])->assertForbidden();
    }

    public function test_inactive_client_cannot_reuse_an_existing_application_form_snapshot(): void
    {
        [$client, $application] = $this->personalApplication();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($client)->get(route('client.applications.show', $application->public_id))->assertOk()->getContent(),
            'application-details-form',
        );

        $client->forceFill(['is_active' => false])->save();
        $this->actingAs($client->fresh());

        $this->livewireUpdate($snapshot, 'save')->assertForbidden();
    }

    public function test_unverified_client_cannot_reuse_an_existing_application_form_snapshot(): void
    {
        [$client, $application] = $this->personalApplication();
        $snapshot = $this->componentSnapshot(
            $this->actingAs($client)->get(route('client.applications.show', $application->public_id))->assertOk()->getContent(),
            'application-details-form',
        );

        $client->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($client->fresh());

        $this->livewireUpdate($snapshot, 'save')->assertForbidden();
    }

    public function test_eligible_admin_and_client_can_continue_using_existing_snapshots(): void
    {
        $admin = $this->admin();
        $adminSnapshot = $this->componentSnapshot(
            $this->actingAs($admin->user)->get(route('admin.applications.index'))->assertOk()->getContent(),
            'admin.application-queue',
        );
        $this->livewireUpdate($adminSnapshot, 'setFilter', ['review'])->assertOk();

        [$client, $application] = $this->personalApplication();
        $clientSnapshot = $this->componentSnapshot(
            $this->actingAs($client)->get(route('client.applications.show', $application->public_id))->assertOk()->getContent(),
            'application-details-form',
        );
        $this->livewireUpdate($clientSnapshot, 'save')->assertOk();
    }

    private function livewireUpdate(string $snapshot, string $method, array $params = []): TestResponse
    {
        return $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => [],
                'calls' => [[
                    'path' => '',
                    'method' => $method,
                    'params' => $params,
                ]],
            ]],
        ], ['X-Livewire' => 'true']);
    }

    private function componentSnapshot(string $html, string $componentName): string
    {
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);

        foreach ($matches[1] as $encodedSnapshot) {
            $snapshot = html_entity_decode($encodedSnapshot, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decoded = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);

            if (($decoded['memo']['name'] ?? null) === $componentName) {
                return $snapshot;
            }
        }

        $this->fail("Livewire component snapshot [{$componentName}] was not found.");
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

    /** @return array{User, Application} */
    private function personalApplication(): array
    {
        $client = User::factory()->create();
        $service = Service::factory()->create(['code' => 'NPWP_PERSONAL']);
        $application = Application::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
        ]);
        $application->personalDetails()->create(['name' => 'Klien Snapshot']);

        return [$client, $application];
    }
}
