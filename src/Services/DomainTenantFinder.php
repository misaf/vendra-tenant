<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as SpatieTenantFinder;

final class DomainTenantFinder extends SpatieTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $tenantDomain = TenantDomain::query()
            ->with('tenant')
            ->where('name', $request->getHost())
            ->where('status', true)
            ->whereHas('tenant', fn(Builder $query): Builder => $query->where('status', true))
            ->first();

        if ( ! $tenantDomain instanceof TenantDomain) {
            return null;
        }

        $tenant = $tenantDomain->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
