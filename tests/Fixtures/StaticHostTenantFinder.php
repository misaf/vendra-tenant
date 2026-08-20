<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * A host finder that resolves nothing from domains at all — it maps a fixed host
 * to a fixed tenant. Its only job is to prove the port really is a port: an
 * application can resolve tenants by subdomain, header, API key or account
 * without `misaf/vendra-store`'s domain tables existing.
 */
final class StaticHostTenantFinder implements HostTenantFinder
{
    public function __construct(
        private readonly string $host,
        private readonly IsTenant $tenant,
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
