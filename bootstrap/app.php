<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\Customer;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (Railway, Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => Admin::class,
            'customer' => Customer::class,
            'permission' => CheckPermission::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'customer.verified' => \App\Http\Middleware\EnsureCustomerEmailIsVerified::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

