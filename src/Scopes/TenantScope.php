<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Misaf\VendraTenant\Models\Tenant;

final class TenantScope implements Scope
{
    /**
     * @param Builder<Model> $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->hasCurrentTenant()) {
            $tenantId = $this->getCurrentTenantId();
            $builder->where($this->getTenantColumn($model), $tenantId);
        }
    }

    private function hasCurrentTenant(): bool
    {
        return app()->has('currentTenant');
    }

    private function getCurrentTenantId(): int
    {
        return Tenant::current()->id;
    }

    private function getTenantColumn(Model $model): string
    {
        return $model->getTable() . '.tenant_id';
    }
}
