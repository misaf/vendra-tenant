<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Concerns;

/**
 * Default {@see \Misaf\VendraTenant\Contracts\TenantContract} implementation for
 * a model that stores a `name` and a `slug`.
 *
 * A tenant model that names its slug column differently overrides only
 * {@see getTenantSlugName()} — the accessor and every query the engine builds
 * follow from it. The primary key needs no override at all: Eloquent's own
 * `getKeyName()` already reports it.
 */
trait IsTenantModel
{
    public function getTenantKey(): int
    {
        $key = $this->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    public function getTenantName(): string
    {
        return $this->tenantStringAttribute('name');
    }

    public function getTenantSlug(): string
    {
        return $this->tenantStringAttribute($this->getTenantSlugName());
    }

    public function getTenantSlugName(): string
    {
        return 'slug';
    }

    public function getTenantLocale(): ?string
    {
        return $this->tenantOptionalStringAttribute('locale');
    }

    public function getTenantTimezone(): ?string
    {
        return $this->tenantOptionalStringAttribute('timezone');
    }

    /**
     * A string attribute the tenant may simply not have.
     *
     * Blank and absent both read as null, so a model without the column, and
     * one whose column is empty, both mean "no opinion, keep the platform's".
     * The existence check is not defensive padding: under
     * `preventAccessingMissingAttributes()` reading a column a tenant model
     * never declared throws, and a `Company` or `Workspace` that has no locale
     * is the normal case this trait exists to serve.
     */
    private function tenantOptionalStringAttribute(string $attribute): ?string
    {
        if ( ! $this->hasAttribute($attribute)) {
            return null;
        }

        $value = $this->getAttribute($attribute);

        return is_string($value) && '' !== mb_trim($value) ? mb_trim($value) : null;
    }

    private function tenantStringAttribute(string $attribute): string
    {
        $value = $this->getAttribute($attribute);

        return is_scalar($value) ? (string) $value : '';
    }
}
