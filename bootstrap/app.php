<?php

use App\Exceptions\FileSecurityException;
use App\Exceptions\InvalidApplicationTransition;
use App\Exceptions\PaymentGatewayException;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureClient;
use App\Http\Middleware\ReadOnlySession;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'client' => EnsureClient::class,
        ]);
        $middleware->prependToPriorityList(ShareErrorsFromSession::class, ReadOnlySession::class);
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (DomainException|FileSecurityException|PaymentGatewayException $exception, Request $request) {
            $message = $exception instanceof PaymentGatewayException
                ? 'Pembayaran belum dapat diproses. Silakan coba lagi atau hubungi admin.'
                : $exception->getMessage();

            if ($request->expectsJson() || $request->is('webhooks/*')) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['error' => $message])->withInput();
        });
        $exceptions->render(function (InvalidApplicationTransition $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Perubahan status tidak tersedia pada tahap ini.'], 422);
            }

            return back()->withErrors(['error' => 'Perubahan status tidak tersedia pada tahap ini.'])->withInput();
        });
    })->create();
