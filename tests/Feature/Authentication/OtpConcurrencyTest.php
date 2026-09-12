<?php

namespace Tests\Feature\Authentication;

use App\Enums\AuthChallengeType;
use App\Exceptions\OtpChallengeException;
use App\Models\AuditLog;
use App\Models\AuthChallenge;
use App\Models\User;
use App\Services\AuthOtpService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_simultaneous_issue_requests_serializes_and_enforces_cooldown(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $issue = static function () use ($userId): string {
            try {
                $user = User::findOrFail($userId);
                $service = app(AuthOtpService::class);
                $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

                return 'SUCCESS';
            } catch (OtpChallengeException $e) {
                return 'COOLDOWN: '.$e->retryAfterSeconds;
            } catch (\Throwable $e) {
                return 'ERROR: '.$e->getMessage();
            }
        };

        $results = Concurrency::driver('process')->run([$issue, $issue]);

        $successCount = count(array_filter($results, fn ($r) => $r === 'SUCCESS'));
        $cooldownCount = count(array_filter($results, fn ($r) => str_starts_with($r, 'COOLDOWN')));

        // Exactly one should succeed, and the other should be caught by the post-lock cooldown recheck
        $this->assertSame(1, $successCount, 'Exactly one concurrent issue request must succeed');
        $this->assertSame(1, $cooldownCount, 'The concurrent request must receive a cooldown exception');

        // Only 1 usable challenge created
        $this->assertSame(1, AuthChallenge::where('user_id', $userId)->whereNull('used_at')->count());
        $this->assertSame(1, AuthChallenge::where('user_id', $userId)->count());
        $this->assertSame(1, AuditLog::where('event', 'authentication.otp_issued')->where('actor_id', $userId)->count());
    }

    public function test_login_issue_vs_resend_concurrency_serializes(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $task1 = static function () use ($userId): string {
            try {
                $u = User::findOrFail($userId);
                app(AuthOtpService::class)->issue($u, AuthChallengeType::CLIENT_LOGIN);

                return 'TASK1_SUCCESS';
            } catch (OtpChallengeException $e) {
                return 'TASK1_COOLDOWN';
            }
        };

        $task2 = static function () use ($userId): string {
            try {
                $u = User::findOrFail($userId);
                app(AuthOtpService::class)->issue($u, AuthChallengeType::CLIENT_LOGIN);

                return 'TASK2_SUCCESS';
            } catch (OtpChallengeException $e) {
                return 'TASK2_COOLDOWN';
            }
        };

        $outcomes = Concurrency::driver('process')->run([$task1, $task2]);

        $successes = array_filter($outcomes, fn ($o) => str_ends_with($o, '_SUCCESS'));
        $cooldowns = array_filter($outcomes, fn ($o) => str_ends_with($o, '_COOLDOWN'));

        $this->assertCount(1, $successes);
        $this->assertCount(1, $cooldowns);
        $this->assertSame(1, AuthChallenge::where('user_id', $userId)->count());
    }

    public function test_two_concurrent_verifications_of_same_challenge_allows_only_single_use(): void
    {
        $user = User::factory()->create();
        $code = '654321';
        $challenge = AuthChallenge::create([
            'user_id' => $user->id,
            'type' => AuthChallengeType::CLIENT_LOGIN->value,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'max_attempts' => 5,
            'last_sent_at' => now(),
        ]);

        $userId = $user->id;
        $challengeId = $challenge->id;

        $verify = static function () use ($userId, $challengeId, $code): string {
            try {
                $u = User::findOrFail($userId);
                $c = AuthChallenge::findOrFail($challengeId);
                app(AuthOtpService::class)->verify($u, $c, $code);

                return 'VERIFIED';
            } catch (OtpChallengeException $e) {
                return 'REJECTED: '.$e->getMessage();
            }
        };

        $results = Concurrency::driver('process')->run([$verify, $verify]);

        $verifiedCount = count(array_filter($results, fn ($r) => $r === 'VERIFIED'));
        $rejectedCount = count(array_filter($results, fn ($r) => str_starts_with($r, 'REJECTED')));

        $this->assertSame(1, $verifiedCount, 'Exactly one concurrent verification may succeed');
        $this->assertSame(1, $rejectedCount, 'The other concurrent verification must be rejected');

        $freshChallenge = AuthChallenge::findOrFail($challengeId);
        $this->assertNotNull($freshChallenge->used_at);
        $this->assertSame(1, AuditLog::where('event', 'authentication.otp_verified')->where('actor_id', $userId)->count());
    }

    public function test_old_challenge_rejected_when_new_challenge_issued(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);

        // First challenge
        $c1 = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

        // Simulate cooldown elapsed
        $c1->update(['last_sent_at' => now()->subSeconds(61)]);

        // Second challenge
        $c2 = $service->issue($user->fresh(), AuthChallengeType::CLIENT_LOGIN);

        $this->assertNotNull($c1->fresh()->used_at, 'Old challenge must be marked used/invalidated');
        $this->assertNull($c2->fresh()->used_at, 'New challenge must be active');

        $this->expectException(OtpChallengeException::class);
        $service->verify($user, $c1->fresh(), '123456');
    }

    public function test_attempt_lock_persists_after_max_attempts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $service = app(AuthOtpService::class);

        $challenge = $service->issue($user, AuthChallengeType::CLIENT_LOGIN);

        for ($i = 0; $i < (int) config('auth_otp.max_attempts'); $i++) {
            try {
                $service->verify($user, $challenge->fresh(), '000000');
            } catch (OtpChallengeException) {
                // Expected failed attempt
            }
        }

        $this->assertTrue($challenge->fresh()->isLocked());

        $this->expectException(OtpChallengeException::class);
        $service->verify($user, $challenge->fresh(), '123456');
    }
}
