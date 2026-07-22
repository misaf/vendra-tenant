<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Support\Facades\Validator;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;
use UnexpectedValueException;

final class ReplaceTenantDomainAction
{
    /**
     * Replace a property's active domain, retaining the previous one as history.
     *
     * The current active domain (status = true) is demoted to a replaced
     * history record (status = false) and soft-deleted, so it stops resolving
     * but stays visible behind the trashed filter. A fresh active domain is
     * then created. Runs in the tenant's own context so the domain records are
     * scoped to this tenant regardless of the currently active tenant.
     */
    public function execute(Tenant $tenant, string $domain): TenantDomain
    {
        $domain = TenantDomain::normalizeDomain($domain);
        Validator::make(
            ['domain' => $domain],
            ['domain' => TenantDomain::activeDomainRules()],
        )->validate();

        $tenantDomain = $tenant->execute(function () use ($tenant, $domain): TenantDomain {
            $tenant->tenantDomains()
                ->where('status', true)
                ->get()
                ->each(function (TenantDomain $current): void {
                    $current->forceFill(['status' => false])->save();
                    $current->delete();
                });

            return $tenant->tenantDomains()->create([
                'name'   => $domain,
                'slug'   => $domain,
                'status' => true,
            ]);
        });

        if ( ! $tenantDomain instanceof TenantDomain) {
            throw new UnexpectedValueException('Replacing a tenant domain did not return a domain model.');
        }

        return $tenantDomain;
    }
}
