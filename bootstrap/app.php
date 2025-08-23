<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; // Make sure this is imported for the schedule closure

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php', // This line typically handles command auto-discovery
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    // --- START: Corrected Scheduling Configuration for Laravel 12 ---
    // This is the dedicated method for defining scheduled tasks.
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('invoices:generate')->dailyAt('01:00');
        $schedule->command('fees:apply-late')->dailyAt('02:00');
    })
    // --- END: Corrected Scheduling Configuration ---
    ->create();