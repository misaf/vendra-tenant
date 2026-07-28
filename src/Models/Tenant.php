<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Concerns\HasFeatures;
use Misaf\VendraSupport\Contracts\ShouldLogActivity;
use Misaf\VendraSupport\Tenancy\Scopes\TeamScope;
use Misaf\VendraSupport\Tenancy\Scopes\TenantScope;
use Misaf\VendraTenant\Database\Factories\TenantFactory;
use Misaf\VendraTenant\Enums\TenantProvisioningStatus;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $reseller_id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property bool $active
 * @property Carbon|null $billing_suspended_at
 * @property TenantProvisioningStatus $provisioning_status
 * @property bool $provisioning_should_seed
 * @property Carbon|null $provisioning_seeded_at
 * @property Carbon|null $routes_cached_at
 * @property Carbon|null $provisioned_at
 * @property Carbon|null $provisioning_failed_at
 * @property string|null $provisioning_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['reseller_id', 'name', 'description', 'slug', 'active', 'provisioning_status', 'provisioning_should_seed'])]
#[UseFactory(TenantFactory::class)]
final class Tenant extends SpatieTenant implements ShouldLogActivity
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasFeatures;

    use HasSlug;
    use SoftDeletes;

    /**
     * Cascade a property's domains through its own lifecycle so no orphaned
     * domain keeps resolving. The active domain (active = true) follows the
     * property; replaced history domains (active = false, already trashed) are
     * left untouched on soft delete and only purged on force delete. Each
     * callback runs in the tenant's own context so
     * {@see TenantScope} on the domains resolves to
     * this tenant, not whatever tenant is currently active in the request.
     */
    protected static function booted(): void
    {
        static::deleting(function (Tenant $tenant): void {
            $tenant->execute(function () use ($tenant): void {
                if ($tenant->isForceDeleting()) {
                    $tenant->tenantDomains()->withTrashed()->forceDelete();

                    return;
                }

                $tenant->tenantDomains()->where('active', true)->delete();
            });
        });

        static::restored(function (Tenant $tenant): void {
            $tenant->execute(fn() => $tenant->tenantDomains()->onlyTrashed()->where('active', true)->restore());
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id'                       => 'integer',
            'reseller_id'              => 'integer',
            'name'                     => 'string',
            'description'              => 'string',
            'slug'                     => 'string',
            'active'                   => 'boolean',
            'billing_suspended_at'     => 'datetime',
            'provisioning_status'      => TenantProvisioningStatus::class,
            'provisioning_should_seed' => 'boolean',
            'provisioning_seeded_at'   => 'datetime',
            'routes_cached_at'         => 'datetime',
            'provisioned_at'           => 'datetime',
            'provisioning_failed_at'   => 'datetime',
            'provisioning_error'       => 'string',
        ];
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }

    /**
     * Limit the query to tenants that may currently serve requests.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeAccessible(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNull('billing_suspended_at')
            ->where('provisioning_status', TenantProvisioningStatus::Ready);
    }

    /**
     * @return HasMany<TenantDomain, $this>
     */
    public function tenantDomains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * A property's domains are always scoped to itself by the relationship's
     * foreign key, so the tenant/team global scopes (which target the currently
     * active tenant) must be dropped to read them from another tenant's context
     * such as the console or reseller panels.
     *
     * @return HasMany<TenantDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->tenantDomains()->withoutGlobalScopes([TenantScope::class, TeamScope::class]);
    }

    /**
     * The name of the property's active (resolving) domain, if any.
     */
    public function activeDomainName(): ?string
    {
        return $this->domains()->where('active', true)->value('name');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }
}
