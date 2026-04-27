<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Scopes\TenantScope;

trait BelongsToTenant
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::addGlobalScope('team', function (Builder $query): void {
            if (auth()->hasUser()) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function ($model): void {
            if ($tenantId = static::getCurrentTenantId()) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    private static function getCurrentTenantId(): ?int
    {
        return app()->has('currentTenant') ? Tenant::current()->id : null;
    }
}
