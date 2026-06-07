<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

/**
 * User provider for authentication and password resets that ignores the tenant
 * global scope.
 *
 * Authentication is a platform-level concern: a user must be found by their
 * credentials regardless of which tenant the current request resolved to.
 * Platform users (super admins) have tenant_id = NULL and would otherwise be
 * filtered out by the BelongsToTenant scope whenever a tenant is current
 * (e.g. single-tenancy mode resolves a tenant even on the login page).
 *
 * This only affects credential/token/id lookups during auth. All ordinary data
 * access keeps the strict `tenant_id = current tenant` scope, so tenant
 * isolation is preserved everywhere else.
 */
class TenantlessUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null): Builder
    {
        return parent::newModelQuery($model)->withoutGlobalScope('tenant');
    }
}
