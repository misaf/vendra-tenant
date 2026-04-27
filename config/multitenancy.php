<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Services\DomainTenantFinder;

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => DomainTenantFinder::class,

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\SwitchTenantTask
     */
    'switch_tenant_tasks' => [
        // Misaf\Tenant\Tasks\SwitchFacadesTask::class,
        Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
        Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
        // Misaf\Tenant\Tasks\SwitchMailTask::class,
        // Misaf\Tenant\Tasks\SwitchAppTask::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must  extend `Spatie\Multitenancy\Models\Tenant::class` or
     * implement `Spatie\Multitenancy\Contracts\IsTenant::class` interface
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'queues_are_tenant_aware_by_default' => true,
];
