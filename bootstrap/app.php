<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // The current tenant must be resolved BEFORE route-model binding runs,
        // otherwise the BelongsToTenant global scope is inactive while a bound
        // model is fetched — letting an authenticated user load another tenant's
        // record by ID. Force SetCurrentTenant ahead of SubstituteBindings.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\SetCurrentTenant::class,
        );

        // PayHere posts to /payhere/notify server-to-server (no session/CSRF token).
        $middleware->validateCsrfTokens(except: ['payhere/notify']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'tenant' => \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            'valid.tenant' => \App\Http\Middleware\EnsureValidTenant::class,
            'tenant.active' => \App\Http\Middleware\EnsureTenantActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
