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

    private function tenantStringAttribute(string $attribute): string
    {
        $value = $this->getAttribute($attribute);

        return is_scalar($value) ? (string) $value : '';
    }
}
