<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Scopes;

use Illuminate\Database\Eloquent\Scope;

final class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // If tenant was already resolved by TenantScope, skip team fallback.
        if (null !== app(TenantResolver::class)->getTenantId()) {
            return;
        }

        $tenantId = auth()->user()?->tenant_id;

        if (null !== $tenantId) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }
}
