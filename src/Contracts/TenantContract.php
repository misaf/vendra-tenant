<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Contracts;

use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * ```php
 * final class Company extends SpatieTenant implements TenantContract
 * {
 *     use IsTenantModel;
 * }
 * ```
 */
interface TenantContract extends IsTenant
{
    public function getTenantKey(): int;

    /**
     * Get the tenant's display name, used for the app name and mail headers.
     */
    public function getTenantName(): string;

    public function getTenantSlug(): string;

    /**
     * Get the tenant's locale, or null to keep the platform's.
     */
    public function getTenantLocale(): ?string;

    /**
     * Get the tenant's timezone, or null to keep the platform's.
     */
    public function getTenantTimezone(): ?string;

    /**
     * Get the name of the column holding {@see getTenantSlug()}, for queries.
     */
    public function getTenantSlugName(): string;
}
