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

    public function issue(User $user, AuthChallengeType $type): AuthChallenge
    {
        $latest = $user->authChallenges()
            ->where('type', $type->value)
            ->whereNull('used_at')
            ->latest('created_at')
            ->first();
        if ($latest?->last_sent_at && now()->diffInSeconds($latest->last_sent_at) < (int) config('auth_otp.resend_cooldown_seconds')) {
            throw new OtpChallengeException('Kode OTP baru belum dapat dikirim. Silakan tunggu sebentar.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->authChallenges()->where('type', $type->value)->whereNull('used_at')->update(['used_at' => now()]);
        $challenge = $user->authChallenges()->create([
            'type' => $type->value,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('auth_otp.expire_minutes')),
            'max_attempts' => (int) config('auth_otp.max_attempts'),
            'last_sent_at' => now(),
            'request_ip' => request()?->ip(),
        ]);

        $user->notify(new LoginOtpNotification($code));
        $this->audit->record('authentication.otp_issued', $user, ['type' => $type->value], $user);

        return $challenge;
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
        request()->session()->regenerate();
        request()->session()->forget(['pending_auth_user_id', 'pending_auth_challenge_id', 'pending_auth_type']);
    }

    public function resend(User $user, AuthChallengeType $type): AuthChallenge
    {
        return $this->issue($user, $type);
    }
}
