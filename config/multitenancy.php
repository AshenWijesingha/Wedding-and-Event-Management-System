<?php

return [
    /*
     * The model to use as Tenant model. Must extend Spatie\Multitenancy\Models\Tenant.
     */
    'tenant_model' => \App\Models\Tenant::class,

    /*
     * The key used to bind the current tenant in the container.
     * Also used by BelongsToTenant trait via app('currentTenant').
     */
    'current_tenant_container_key' => 'currentTenant',

    /*
     * Resolves the current tenant from the request.
     * Identifies by exact domain first, then by subdomain slug.
     */
    'tenant_finder' => \App\Multitenancy\DomainTenantFinder::class,

    /*
     * Tasks run in order when a tenant is made current.
     * For column-based multi-tenancy, no DB switch is needed.
     */
    'switch_tenant_tasks' => [
        \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
    ],

    /*
     * Fields used to search for tenants via artisan commands.
     */
    'tenant_artisan_search_fields' => [
        'id',
        'slug',
        'domain',
    ],

    /*
     * Queued jobs dispatched within a tenant context will
     * automatically re-apply that tenant when processed.
     */
    'queues_are_tenant_aware_by_default' => true,
];
