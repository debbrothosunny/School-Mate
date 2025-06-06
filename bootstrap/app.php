<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register your 'role' middleware alias here:
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // You can now use the 'role' alias directly in your routes
            // or if you want to apply it to ALL web routes, you can add it here:
            // 'role:admin', // Example: if you want 'admin' role on all web routes
        ]);

        // If you want to apply 'role' middleware to specific routes,
        // you'll use it in your routes/web.php like this:
        // Route::middleware('role:admin')->group(function () { ... });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();