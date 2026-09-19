<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Concerns;

/**
 * Override {@see getTenantSlugName()} if the slug column has another name.
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
     * Get an optional string attribute, treating a blank or missing one as null.
     *
     * The existence check avoids `preventAccessingMissingAttributes()` exceptions.
     */
    private function tenantOptionalStringAttribute(string $attribute): ?string
    {
        if (! $this->hasAttribute($attribute)) {
            return null;
        }

        $value = $this->getAttribute($attribute);

        return is_string($value) && mb_trim($value) !== '' ? mb_trim($value) : null;
    }

    private function tenantStringAttribute(string $attribute): string
    {
        $value = $this->getAttribute($attribute);

        return is_scalar($value) ? (string) $value : '';
    }
}
