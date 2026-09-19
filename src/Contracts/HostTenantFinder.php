<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Contracts;

use Misaf\VendraTenant\Support\NullHostTenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * Applications bind an adapter; {@see NullHostTenantFinder} resolves nothing.
 */
interface HostTenantFinder
{
    /**
     * Resolve a tenant for any host it owns, admin surfaces included.
     */
    public function findForHost(string $host): ?IsTenant;

    /**
     * Resolve a tenant from one of its administration hosts only.
     */
    public function findForAdminHost(string $host): ?IsTenant;

    public function findForOrigin(string $origin): ?IsTenant;
}
