<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Testing\FakePaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? (auth()->user()->isAdmin() ? 'admin.dashboard' : 'client.dashboard') : 'login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login')->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:auth-login')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::get('/email/verify/{publicId}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:auth-login')->name('verification.send');
Route::get('/auth/otp', [AuthController::class, 'showOtp'])->name('auth.otp');
Route::post('/auth/otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:auth-otp')->name('auth.otp.verify');
Route::post('/auth/otp/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:auth-otp-resend')->name('auth.otp.resend');

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:auth-login')->name('admin.login.store');
Route::get('/admin/otp', [AuthController::class, 'showOtp'])->name('admin.otp');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

require __DIR__.'/client.php';
require __DIR__.'/admin.php';
require __DIR__.'/webhooks.php';

if (in_array(app()->environment(), ['local', 'testing'], true) && config('services.xendit.driver') === 'fake') {
    Route::middleware(['auth', 'verified', 'client'])
        ->prefix('testing/fake-payments')
        ->name('testing.fake-payments.')
        ->group(function (): void {
            Route::get('/{paymentId}/checkout', [FakePaymentController::class, 'show'])->name('checkout');
            Route::post('/{paymentId}/complete', [FakePaymentController::class, 'complete'])->name('complete');
        });
}
