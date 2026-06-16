<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class TenantRule
{
    /**
     * An `exists` validation rule scoped to the current tenant, so a request can
     * only reference rows that belong to the caller's tenant (prevents referencing
     * another tenant's record by guessing its id). Fails closed when no tenant is
     * in context.
     */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)
            ->where('tenant_id', Tenant::current()?->id);
    }
}
