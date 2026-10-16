<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',          // all routes here get the /api prefix + 'api' group
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1) GLOBAL middleware: runs on every request (web + api).
        $middleware->append(AssignRequestId::class);

        // 2) GROUP middleware: added to the 'api' group only.
        //    prepend = runs early, so even auth errors come back as JSON.
        $middleware->api(prepend: [ForceJsonResponse::class]);

        // 3) ROUTE middleware: a short name you attach to chosen routes ('role:admin').
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    // Listeners are registered by hand in ShipmentServiceProvider.
    ->withEvents(discover: false)
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
