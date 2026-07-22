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
                    ->enabled()
                    ->where('slug', $tenantSlug)
                    ->first();
            }
        }

        if (Str::startsWith($host, 'admin.')) {
            return $this->findForTenantDomain(Str::after($host, 'admin.'));
        }

        return null;
    }

    private function findForTenantDomain(string $host): ?IsTenant
    {
        return Tenant::query()
            ->enabled()
            ->whereHas('tenantDomains', fn(Builder $query): Builder => $query
                ->where('name', Str::lower($host))
                ->where('status', true))
            ->first();
    }
}
