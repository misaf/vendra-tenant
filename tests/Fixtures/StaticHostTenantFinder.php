<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * Map a fixed host to a fixed tenant, without `misaf/vendra-store`'s domain tables.
 */
final readonly class StaticHostTenantFinder implements HostTenantFinder
{
    public function __construct(
        private string $host,
        private IsTenant $tenant,
    ) {}

    public function findForHost(string $host): ?IsTenant
    {
        return $host === $this->host ? $this->tenant : null;
    }

    public function findForAdminHost(string $host): ?IsTenant
    {
        return $this->findForHost($host);
    }

    public function findForOrigin(string $origin): ?IsTenant
    {
        return $this->findForHost((string) parse_url($origin, PHP_URL_HOST));
    }
}
