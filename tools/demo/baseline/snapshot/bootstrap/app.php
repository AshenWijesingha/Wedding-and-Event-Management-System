<?php

use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\EnsureValidTenant;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrentTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);

        // The current tenant must be resolved BEFORE route-model binding runs,
        // otherwise the BelongsToTenant global scope is inactive while a bound
        // model is fetched — letting an authenticated user load another tenant's
        // record by ID. Force SetCurrentTenant ahead of SubstituteBindings.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetCurrentTenant::class,
        );

        // PayHere posts to /payhere/notify server-to-server (no session/CSRF token).
        $middleware->validateCsrfTokens(except: ['payhere/notify']);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'tenant' => NeedsTenant::class,
            'valid.tenant' => EnsureValidTenant::class,
            'tenant.active' => EnsureTenantActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
