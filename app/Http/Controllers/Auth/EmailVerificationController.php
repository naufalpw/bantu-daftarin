<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function verify(Request $request, string $publicId, string $hash): RedirectResponse
    {
        $user = User::where('public_id', $publicId)->firstOrFail();
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->audit->record('authentication.email_verified', $user, [], $user, $request);
        }

        return redirect()->route('login')->with('status', 'Email berhasil diverifikasi. Silakan login.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = strtolower((string) $request->input('email'));
        if ($user = User::where('email', $email)->whereNull('email_verified_at')->first()) {
            $user->sendEmailVerificationNotification();
        }

        return back()
            ->with('status', 'Jika email terdaftar dan belum diverifikasi, tautan verifikasi akan dikirim.')
            ->with('verification_email', $email);
    }
}
