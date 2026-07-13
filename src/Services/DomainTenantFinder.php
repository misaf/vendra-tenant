<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        return Tenant::query()
            ->enabled()
            ->whereHas('tenantDomains', fn(Builder $query): Builder => $query
                ->where('name', $host)
                ->where('status', true))
            ->first();
    }
}
