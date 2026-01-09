<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Only apply tenant scoping if multi-tenancy is enabled
        if (config('eventpro.tenancy_mode') === 'single') {
            return;
        }

        // Auto-scope queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = app('currentTenant')) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenant->id);
            }
        });

        // Auto-assign tenant on create
        static::creating(function (Model $model) {
            if ($tenant = app('currentTenant')) {
                if (!$model->tenant_id) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope query to a specific tenant.
     */
    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    /**
     * Remove the tenant scope from query.
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
