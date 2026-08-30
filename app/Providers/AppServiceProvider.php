<?php

namespace App\Providers;

use App\Contracts\MalwareScanner;
use App\Contracts\PaymentGateway;
use App\Models\Application;
use App\Models\ChatThread;
use App\Models\Document;
use App\Models\Payment;
use App\Models\ResultDocument;
use App\Policies\ApplicationPolicy;
use App\Policies\ChatThreadPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ResultDocumentPolicy;
use App\Services\ClamAvMalwareScanner;
use App\Services\FakePaymentGateway;
use App\Services\TestingMalwareScanner;
use App\Services\XenditInvoiceGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => config('services.xendit.driver') === 'fake' ? new FakePaymentGateway : new XenditInvoiceGateway);
        $this->app->bind(MalwareScanner::class, fn () => config('files.malware_scan_driver') === 'testing' ? new TestingMalwareScanner : new ClamAvMalwareScanner);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(ResultDocument::class, ResultDocumentPolicy::class);
        Gate::policy(ChatThread::class, ChatThreadPolicy::class);

        RateLimiter::for('auth-login', fn ($request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('auth-otp', fn ($request) => Limit::perMinute(10)->by($request->session()->get('pending_auth_user_id', 'guest').'|'.$request->ip()));
        RateLimiter::for('auth-otp-resend', fn ($request) => Limit::perMinute(3)->by($request->session()->get('pending_auth_user_id', 'guest').'|'.$request->ip()));
        RateLimiter::for('webhook', fn ($request) => Limit::perMinute(120)->by($request->ip()));
    }
}
