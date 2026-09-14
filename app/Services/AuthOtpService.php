<?php

namespace App\Services;

use App\Enums\AuthChallengeType;
use App\Exceptions\OtpChallengeException;
use App\Models\AuthChallenge;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthOtpService
{
    public function __construct(private readonly AuditService $audit) {}

    public function resendCooldownRemaining(User $user, AuthChallengeType $type): int
    {
        $latest = $user->authChallenges()
            ->where('type', $type->value)
            ->whereNull('used_at')
            ->whereNotNull('last_sent_at')
            ->latest('last_sent_at')
            ->first();

        if (! $latest?->last_sent_at) {
            return 0;
        }

        $cooldown = max(0, (int) config('auth_otp.resend_cooldown_seconds'));
        $availableAt = $latest->last_sent_at->copy()->addSeconds($cooldown);

        return max(0, $availableAt->getTimestamp() - now()->getTimestamp());
    }

    public function issue(User $user, AuthChallengeType $type): AuthChallenge
    {
        return DB::transaction(function () use ($user, $type): AuthChallenge {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $cooldownRemaining = $this->resendCooldownRemaining($lockedUser, $type);
            if ($cooldownRemaining > 0) {
                throw OtpChallengeException::cooldown($cooldownRemaining);
            }

            $issuedAt = now();
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $lockedUser->authChallenges()->where('type', $type->value)->whereNull('used_at')->update(['used_at' => now()]);
            $challenge = $lockedUser->authChallenges()->create([
                'type' => $type->value,
                'code_hash' => Hash::make($code),
                'expires_at' => $issuedAt->copy()->addMinutes((int) config('auth_otp.expire_minutes')),
                'max_attempts' => (int) config('auth_otp.max_attempts'),
                'last_sent_at' => $issuedAt,
                'request_ip' => request()?->ip(),
            ]);

            $this->audit->record('authentication.otp_issued', $lockedUser, ['type' => $type->value], $lockedUser);

            DB::afterCommit(function () use ($lockedUser, $code) {
                $lockedUser->notify(new LoginOtpNotification($code));
            });

            return $challenge;
        });
    }

    public function verify(User $user, AuthChallenge $challenge, string $code): void
    {
        $failedAttempt = false;

        DB::transaction(function () use ($user, $challenge, $code, &$failedAttempt): void {
            $locked = AuthChallenge::query()->lockForUpdate()->findOrFail($challenge->getKey());

            if ($locked->user_id !== $user->getKey() || $locked->isUsed() || $locked->isExpired() || $locked->isLocked()) {
                throw new OtpChallengeException('Kode OTP tidak valid atau sudah kedaluwarsa.');
            }
            if (($locked->type instanceof AuthChallengeType ? $locked->type : AuthChallengeType::from($locked->type)) === AuthChallengeType::ADMIN_LOGIN && ! $user->isAdmin()) {
                throw new OtpChallengeException('Kode OTP tidak valid atau sudah kedaluwarsa.');
            }

            if (! Hash::check($code, $locked->code_hash)) {
                $locked->increment('attempts');
                if ($locked->attempts >= $locked->max_attempts) {
                    $locked->forceFill(['locked_until' => now()->addMinutes(15)])->save();
                }
                $this->audit->record('authentication.otp_failed', $user, ['challenge_id' => $locked->public_id], $user);
                $failedAttempt = true;

                return;
            }

            $locked->forceFill(['used_at' => now()])->save();
            $this->audit->record('authentication.otp_verified', $user, ['type' => $locked->type->value], $user);
        });

        if ($failedAttempt) {
            throw new OtpChallengeException('Kode OTP tidak valid atau sudah kedaluwarsa.');
        }

        Auth::login($user);
        if ($user->isAdmin()) {
            $user->admin?->forceFill(['last_login_at' => now()])->save();
        }
        if (request()->hasSession()) {
            request()->session()->regenerate();
            request()->session()->forget(['pending_auth_user_id', 'pending_auth_challenge_id', 'pending_auth_type']);
        }
    }

    public function resend(User $user, AuthChallengeType $type): AuthChallenge
    {
        return $this->issue($user, $type);
    }
}
