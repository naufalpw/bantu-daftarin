<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email:rfc']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('passwords.sent_if_registered'));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => ['required'], 'email' => ['required', 'email:rfc'], 'password' => ['required', 'confirmed', 'min:12']]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user) use ($request): void {
            $user->forceFill(['password' => Hash::make($request->string('password')->toString()), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }
}
