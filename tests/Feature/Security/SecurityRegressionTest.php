<?php

namespace Tests\Feature\Security;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_routes_are_wired_through_csrf_middleware(): void
    {
        $webGroup = app('router')->getMiddlewareGroups()['web'] ?? [];

        $this->assertTrue(collect($webGroup)->contains(fn (string $middleware): bool => str_contains($middleware, 'ValidateCsrfToken')));
    }

    public function test_registration_cannot_escalate_role_or_disable_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Synthetic Escalation Attempt',
            'email' => 'escalation@example.test',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => false,
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', 'escalation@example.test')->firstOrFail();
        $this->assertSame(UserRole::CLIENT, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->isAdmin());
    }

    public function test_sensitive_primary_keys_are_not_mass_assignable(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $application = Application::create([
            'id' => 999999,
            'public_id' => '00000000-0000-0000-0000-000000000001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ApplicationStatus::DRAFT,
            'price_amount_snapshot' => 100000,
            'currency' => 'IDR',
        ]);

        $this->assertNotSame(999999, $application->id);
        $this->assertNotSame('00000000-0000-0000-0000-000000000001', $application->public_id);
    }

    public function test_login_rate_limit_blocks_repeated_attempts(): void
    {
        RateLimiter::clear('attacker@example.test|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => 'attacker@example.test', 'password' => 'invalid'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), ['email' => 'attacker@example.test', 'password' => 'invalid'])
            ->assertTooManyRequests();
    }

    public function test_sql_like_public_id_input_does_not_bypass_ownership_query(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('client.applications.show', "' OR 1=1 --"))
            ->assertNotFound();
    }

    public function test_security_headers_include_csp_and_referrer_policy(): void
    {
        $response = $this->get('/');

        $response->assertHeader(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'",
        );
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", (string) $response->headers->get('Content-Security-Policy'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_livewire_uses_the_csp_safe_client_without_weakening_the_script_policy(): void
    {
        $response = $this->get('/');

        $this->assertTrue(config('livewire.csp_safe'));
        preg_match('/<script src="([^"]+\/livewire\.csp\.js\?id=[^"]+)"/', $response->getContent(), $scriptMatch);

        $this->assertArrayHasKey(1, $scriptMatch);
        $this->assertStringContainsString('/livewire.csp.js?', $scriptMatch[1]);
        $scriptResponse = $this->get(parse_url($scriptMatch[1], PHP_URL_PATH));
        $scriptResponse->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $scriptResponse->baseResponse);
        $this->assertSame(
            realpath(base_path('vendor/livewire/livewire/dist/livewire.csp.js')),
            realpath($scriptResponse->baseResponse->getFile()->getPathname()),
        );
        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            (string) $response->headers->get('Content-Security-Policy'),
        );
    }

    public function test_blade_sources_do_not_embed_executable_inline_scripts_or_dom_event_handlers(): void
    {
        $bladeFiles = File::allFiles(resource_path('views'));

        foreach ($bladeFiles as $bladeFile) {
            $source = $bladeFile->getContents();

            $this->assertDoesNotMatchRegularExpression('/<script\b/i', $source, $bladeFile->getRelativePathname().' contains an inline script element.');
            $this->assertDoesNotMatchRegularExpression('/\son(?:click|change|submit|load|error|input|keyup|keydown)\s*=/i', $source, $bladeFile->getRelativePathname().' contains an inline DOM event handler.');
        }
    }
}
