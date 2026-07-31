<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Misaf\VendraTenant\Models\Tenant;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as SpatieTenantFinder;

final class DomainTenantFinder extends SpatieTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        return $this->findForHost($request->getHost());
    }

    public function findForHost(string $host): ?IsTenant
    {
        return $this->findForAdminHost($host) ?? $this->findForTenantDomain($host);
    }

    public function findForAdminHost(string $host): ?IsTenant
    {
        $host = Str::lower($host);
        $adminDomain = 'admin.' . config()->string('vendra-tenant.central_host');

        if (Str::endsWith($host, '.' . $adminDomain)) {
            $tenantSlug = Str::beforeLast($host, '.' . $adminDomain);

            if ('' !== $tenantSlug && ! str_contains($tenantSlug, '.')) {
                return Tenant::query()
                    ->accessible()
                    ->where('slug', $tenantSlug)
                    ->first();
            }
        }

        if (Str::startsWith($host, 'admin.')) {
            return $this->findForTenantDomain(Str::after($host, 'admin.'));
        }

        return null;
    }

    /**
     * Resolve the tenant that owns a storefront origin, e.g. "https://shop.com".
     *
     * The canonical API answers on one host for every property, so a storefront
     * identifies itself by the origin it calls from — the same active tenant
     * domain data that backs the CORS allowlist. Admin host shapes are
     * deliberately not accepted here: an origin is not an admin surface.
     */
    public function findForOrigin(string $origin): ?IsTenant
    {
        $host = parse_url($origin, PHP_URL_HOST);

        if ( ! is_string($host) || '' === $host) {
            return null;
        }

        return $this->findForTenantDomain($host);
    }

    private function findForTenantDomain(string $host): ?IsTenant
    {
        return Tenant::query()
            ->accessible()
            ->whereHas('tenantDomains', fn(Builder $query): Builder => $query
                ->where('name', Str::lower($host))
                ->where('active', true))
            ->first();
    }
}
