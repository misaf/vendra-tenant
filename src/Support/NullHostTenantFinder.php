<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Support;

use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

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
