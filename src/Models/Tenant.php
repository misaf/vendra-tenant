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
use Misaf\VendraSupport\Scopes\TeamScope;
use Misaf\VendraSupport\Scopes\TenantScope;
use Misaf\VendraTenant\Database\Factories\TenantFactory;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $account_id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['account_id', 'name', 'description', 'slug', 'status'])]
#[UseFactory(TenantFactory::class)]
final class Tenant extends SpatieTenant implements ShouldLogActivity
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasFeatures;

    use HasSlug;
    use SoftDeletes;

    /**
     * Cascade a website's domains through its own lifecycle so no orphaned
     * domain keeps resolving. The active domain (status = true) follows the
     * website; replaced history domains (status = false, already trashed) are
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

                $tenant->tenantDomains()->where('status', true)->delete();
            });
        });

        static::restored(function (Tenant $tenant): void {
            $tenant->execute(fn() => $tenant->tenantDomains()->onlyTrashed()->where('status', true)->restore());
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id'          => 'integer',
            'account_id'  => 'integer',
            'name'        => 'string',
            'description' => 'string',
            'slug'        => 'string',
            'status'      => 'boolean',
        ];
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('status', false);
    }

    /**
     * @return HasMany<TenantDomain, $this>
     */
    public function tenantDomains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * A website's domains are always scoped to itself by the relationship's
     * foreign key, so the tenant/team global scopes (which target the currently
     * active tenant) must be dropped to read them from another tenant's context
     * such as the platform or account panels.
     *
     * @return HasMany<TenantDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->tenantDomains()->withoutGlobalScopes([TenantScope::class, TeamScope::class]);
    }

    /**
     * The name of the website's active (resolving) domain, if any.
     */
    public function activeDomainName(): ?string
    {
        return $this->domains()->where('status', true)->value('name');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }
}
