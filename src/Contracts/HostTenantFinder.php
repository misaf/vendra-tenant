<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Contracts;

use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * The port through which the engine turns a request host into a tenant.
 *
 * How a host maps to a tenant is business knowledge — Vendra ecommerce resolves
 * it from the store's own domains — so the engine depends on this interface and
 * the concrete application binds the adapter. Without a binding the engine falls
 * back to {@see \Misaf\VendraTenant\Support\NullHostTenantFinder} and simply
 * resolves nothing.
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

    /**
     * Resolve a tenant from a browser origin such as "https://shop.test".
     */
    public function findForOrigin(string $origin): ?IsTenant;
}
