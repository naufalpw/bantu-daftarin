<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuthChallengeType;
use App\Exceptions\OtpChallengeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OtpRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AuthChallenge;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AuthOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthOtpService $otp, private readonly AuditService $audit) {}

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => strtolower($request->string('email')->toString()),
            'password' => $request->string('password')->toString(),
        ]);
        $user->sendEmailVerificationNotification();

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil. Periksa email Anda untuk verifikasi sebelum login.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', strtolower($request->string('email')->toString()))->first();
        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password) || ! $user->is_active) {
            $this->audit->record('authentication.login_failed', null, ['reason' => 'invalid_credentials'], null, $request);

            return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
        }
        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors(['email' => 'Verifikasi email Anda terlebih dahulu.'])->onlyInput('email');
        }
        if ($user->isAdmin() && ! $user->admin?->is_active) {
            return back()->withErrors(['email' => 'Akun admin tidak aktif.'])->onlyInput('email');
        }

        $type = $user->isAdmin() ? AuthChallengeType::ADMIN_LOGIN : AuthChallengeType::CLIENT_LOGIN;
        try {
            $challenge = $this->otp->issue($user, $type);
        } catch (OtpChallengeException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()])->onlyInput('email');
        }

        $request->session()->put([
            'pending_auth_user_id' => $user->getKey(),
            'pending_auth_challenge_id' => $challenge->public_id,
            'pending_auth_type' => $type->value,
        ]);

        return redirect()->route($user->isAdmin() ? 'admin.otp' : 'auth.otp');
    }

    public function showOtp(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('pending_auth_challenge_id')) {
            return redirect()->route('login');
        }

        $type = AuthChallengeType::tryFrom((string) $request->session()->get('pending_auth_type'));
        $user = User::find($request->session()->get('pending_auth_user_id'));
        $resendCooldownSeconds = $user && $type ? $this->otp->resendCooldownRemaining($user, $type) : 0;

        return view('auth.otp', [
            'isAdmin' => $type === AuthChallengeType::ADMIN_LOGIN,
            'resendCooldownSeconds' => $resendCooldownSeconds,
        ]);
    }

    public function verifyOtp(OtpRequest $request): RedirectResponse
    {
        $user = User::findOrFail($request->session()->get('pending_auth_user_id'));
        $challenge = AuthChallenge::query()->where('public_id', $request->session()->get('pending_auth_challenge_id'))->firstOrFail();
        abort_unless($challenge->type->value === $request->session()->get('pending_auth_type'), 403);

        try {
            $this->otp->verify($user, $challenge, $request->string('code')->toString());
        } catch (OtpChallengeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return $user->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('client.dashboard');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->session()->get('pending_auth_user_id'));
        $type = AuthChallengeType::from($request->session()->get('pending_auth_type'));

        try {
            $challenge = $this->otp->resend($user, $type);
            $request->session()->put('pending_auth_challenge_id', $challenge->public_id);
        } catch (OtpChallengeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('status', 'Kode OTP baru telah dikirim.');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
