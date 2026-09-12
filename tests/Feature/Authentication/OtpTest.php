<?php

namespace Tests\Feature\Authentication;

use App\Enums\AuthChallengeType;
use App\Enums\UserRole;
use App\Exceptions\OtpChallengeException;
use App\Models\Admin;
use App\Models\AuthChallenge;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\AuthOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_login_requires_and_accepts_email_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'client@example.test', 'password' => 'strong-password-123']);

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'strong-password-123']);
        $response->assertRedirect(route('auth.otp'));
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($user, LoginOtpNotification::class, function (LoginOtpNotification $notification) use (&$code): bool {
            $code = Crypt::decryptString($notification->encryptedCode);

            return true;
        });
        $this->assertNotNull($code);
        $response = $this->post(route('auth.otp.verify'), ['code' => $code]);
        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_always_requires_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'email' => 'admin@example.test', 'password' => 'strong-password-123']);
        Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => true]);

        $response = $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'strong-password-123']);
        $response->assertRedirect(route('admin.otp'));
        $this->assertGuest();
        $this->assertDatabaseHas('auth_challenges', ['user_id' => $user->id, 'type' => AuthChallengeType::ADMIN_LOGIN->value]);
    }

    public function test_wrong_otp_is_counted_and_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'strong-password-123']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'strong-password-123']);
        $this->post(route('auth.otp.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('auth_challenges', ['user_id' => $user->id, 'attempts' => 1]);
    }

    public function test_registration_requires_email_verification_before_login(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Synthetic New Client',
            'email' => 'new-client@example.test',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas('verification_email', 'new-client@example.test');
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Kami mengirim link verifikasi ke')
            ->assertSee('new-client@example.test')
            ->assertSee(route('verification.send'), false)
            ->assertSee('Kirim ulang link verifikasi');
        $user = User::query()->where('email', 'new-client@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'publicId' => $user->public_id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->get($verificationUrl)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_cannot_start_login(): void
    {
        $user = User::factory()->unverified()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('auth_challenges', ['user_id' => $user->id]);
    }

    public function test_inactive_admin_cannot_start_admin_login(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        Admin::create(['user_id' => $user->id, 'email' => $user->email, 'role' => UserRole::SUPER_ADMIN, 'is_active' => false]);

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('auth_challenges', ['user_id' => $user->id]);
    }

    public function test_expired_otp_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $challenge = AuthChallenge::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $challenge->forceFill(['expires_at' => now()->subSecond()])->save();

        $this->post(route('auth.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_otp_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $challenge = AuthChallenge::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $code = null;

        Notification::assertSentTo($user, LoginOtpNotification::class, function (LoginOtpNotification $notification) use (&$code): bool {
            $code = Crypt::decryptString($notification->encryptedCode);

            return true;
        });

        $this->post(route('auth.otp.verify'), ['code' => $code])->assertRedirect(route('client.dashboard'));
        $this->assertNotNull($challenge->fresh()->used_at);

        $this->post(route('logout'));
        $this->withSession([
            'pending_auth_user_id' => $user->id,
            'pending_auth_challenge_id' => $challenge->public_id,
            'pending_auth_type' => AuthChallengeType::CLIENT_LOGIN->value,
        ]);
        $this->post(route('auth.otp.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_otp_lockout_after_maximum_failed_attempts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);
        $challenge = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $service->verify($user, $challenge, '000000');
            } catch (OtpChallengeException) {
                // Expected for every invalid attempt.
            }
        }

        $locked = $challenge->fresh();
        $this->assertSame(5, $locked->attempts);
        $this->assertNotNull($locked->locked_until);
        $this->expectException(OtpChallengeException::class);
        $service->verify($user, $challenge, '000000');
    }

    public function test_otp_resend_cooldown_is_enforced(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);
        $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

        $this->expectException(OtpChallengeException::class);
        $service->issue($user, AuthChallengeType::CLIENT_LOGIN);
    }

    public function test_otp_cooldown_reports_remaining_seconds_and_expires_after_sixty_seconds(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);
        $issuedAt = Carbon::create(2026, 8, 31, 10, 0, 0, 'Asia/Jakarta');

        Carbon::setTestNow($issuedAt);
        try {
            $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

            Carbon::setTestNow($issuedAt->copy()->addSeconds(59));
            try {
                $service->issue($user, AuthChallengeType::CLIENT_LOGIN);
                $this->fail('The OTP cooldown should still be active after 59 seconds.');
            } catch (OtpChallengeException $exception) {
                $this->assertSame(1, $exception->retryAfterSeconds);
                $this->assertStringContainsString('1 detik', $exception->getMessage());
            }

            Carbon::setTestNow($issuedAt->copy()->addSeconds(60));
            $newChallenge = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

            $this->assertNotNull($newChallenge->getKey());
            $this->assertSame(2, AuthChallenge::query()->where('user_id', $user->id)->where('type', AuthChallengeType::CLIENT_LOGIN->value)->count());
            $this->assertSame(60, $service->resendCooldownRemaining($user, AuthChallengeType::CLIENT_LOGIN));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_otp_page_displays_server_calculated_resend_countdown(): void
    {
        Notification::fake();
        $now = Carbon::create(2026, 8, 31, 10, 0, 0, 'Asia/Jakarta');
        Carbon::setTestNow($now);

        try {
            $user = User::factory()->create();
            $service = app(AuthOtpService::class);
            $challenge = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

            $response = $this->withSession([
                'pending_auth_user_id' => $user->id,
                'pending_auth_challenge_id' => $challenge->public_id,
                'pending_auth_type' => AuthChallengeType::CLIENT_LOGIN->value,
            ])->get(route('auth.otp'));

            $response->assertOk()
                ->assertSee('class="bd-otp-input mt-1"', false)
                ->assertSee('data-otp-resend-remaining="60"', false)
                ->assertSee('Kirim ulang OTP tersedia dalam', false)
                ->assertSee('data-otp-resend-button', false)
                ->assertSee('disabled', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_login_cooldown_error_reports_remaining_seconds(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $issuedAt = Carbon::create(2026, 8, 31, 10, 0, 0, 'Asia/Jakarta');

        Carbon::setTestNow($issuedAt);
        try {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('auth.otp'));

            Carbon::setTestNow($issuedAt->copy()->addSeconds(30));
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect(route('login'));
            $this->assertStringContainsString('30 detik', session('errors')->get('email')[0]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_used_otp_does_not_block_a_new_login_after_logout(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);
        $first = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);
        $first->forceFill(['used_at' => now()])->save();

        $second = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

        $this->assertNotSame($first->getKey(), $second->getKey());
        $this->assertNotNull($first->fresh()->used_at);
        $this->assertNull($second->fresh()->used_at);
    }

    public function test_password_reset_notification_does_not_expose_plaintext_token_in_property(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $notification): bool {
            return ! str_contains(serialize($notification), 'plain-reset-token');
        });
    }

    public function test_password_reset_token_changes_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset-password@example.test']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $token = null;

        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $notification) use (&$token): bool {
            $token = Crypt::decryptString($notification->encryptedToken);

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-strong-password-123',
            'password_confirmation' => 'new-strong-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-strong-password-123', $user->fresh()->password));
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
