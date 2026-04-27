<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Misaf\VendraTenant\Models\Tenant;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

final class DomainTenantFinder extends TenantFinder
{
    /**
     * @param Request $request
     * @return IsTenant|null
     */
    public function findForRequest(Request $request): ?IsTenant
    {
        $rootDomain = $this->extractRootDomain($request->getHost());

        if ( ! $this->isValidDomain($rootDomain)) {
            return null;
        }

        return $this->findTenantByDomain($rootDomain);
    }

    /**
     * @param string $domain
     * @return Tenant|null
     */
    private function findTenantByDomain(string $domain): ?Tenant
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = Config::string('multitenancy.tenant_model');

        return $tenantModel::whereHas('tenantDomains', function (Builder $query) use ($domain): void {
            $query->where('name', $domain)
                ->where('status', true);
        })
            ->where('status', true)
            ->first();
    }

    /**
     * @param string $host
     * @return string
     */
    private function extractRootDomain(string $host): string
    {
        // e.g. sub.domain.tld → domain.tld
        return Str::afterLast(Str::beforeLast($host, '.'), '.') . '.' . Str::afterLast($host, '.');
    }

    /**
     * @param string $domain
     * @return bool
     */
    private function isValidDomain(string $domain): bool
    {
        return ! empty($domain) && false !== filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }
}
