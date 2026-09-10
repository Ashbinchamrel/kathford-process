<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // NOTE: EnsureUserIsActive is NOT added globally — it is used as the
        // 'active' alias on authenticated route groups only. Adding it globally
        // causes an infinite redirect loop on the guest /login page.

        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role'   => \App\Http\Middleware\CheckRole::class,
            'vendor.portal' => \App\Http\Middleware\EnsureVendorPortalSession::class,
        ]);

        $middleware->web(append: [\App\Http\Middleware\SetFiscalYear::class]);
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\SetFiscalYear::class);

        // Trust proxies (for Nginx reverse proxy)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please reload the page and try again.',
                ], 419);
            }

            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            return redirect()
                ->back()
                ->withInput($request->except('_token'))
                ->withErrors(['session' => 'Your page session expired. Please review the form and submit again.']);
        });
    })->create();
