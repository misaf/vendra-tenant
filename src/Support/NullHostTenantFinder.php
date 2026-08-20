<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Support;

use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * The default host finder: an application that does not resolve tenants from
 * hosts (or has not bound its adapter yet) resolves none, rather than the
 * engine guessing at a domain table it does not own.
 */
final class NullHostTenantFinder implements HostTenantFinder
{
    public function findForHost(string $host): ?IsTenant
    {
        return null;
    }

    public function findForAdminHost(string $host): ?IsTenant
    {
        return null;
    }

    public function findForOrigin(string $origin): ?IsTenant
    {
        return null;
    }
}
