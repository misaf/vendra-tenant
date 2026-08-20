<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Contracts;

use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * The contract a concrete tenant model fulfils.
 *
 * "Tenant" here is a technical role, never a business entity: the model that
 * implements this is the application's own aggregate — `Store` in Vendra
 * ecommerce, `Company`, `Workspace`, `Organization` or `Team` elsewhere. The
 * engine only ever needs an identity, a display name and a stable slug; every
 * other attribute stays the business package's own.
 *
 * ```php
 * final class Company extends SpatieTenant implements TenantContract
 * {
 *     use IsTenantModel;
 * }
 * ```
 */
interface TenantContract extends IsTenant
{
    /**
     * The tenant's primary key.
     */
    public function getTenantKey(): int;

    /**
     * A human-readable name, used for the application name and mail headers.
     */
    public function getTenantName(): string;

    /**
     * A URL-safe identifier, used for host resolution and per-tenant mailers.
     */
    public function getTenantSlug(): string;

    /**
     * The name of the column holding {@see getTenantSlug()}.
     *
     * The engine has to *query* the slug, not just read it off a loaded model —
     * looking a tenant up by slug and offering search options both build SQL —
     * so the model is asked for the column name rather than the engine assuming
     * `slug`. Together with Eloquent's own `getKeyName()` this keeps the
     * resolver free of column-name assumptions: `Store` keeps `id`/`slug`,
     * while a `Company` on `company_id`/`code` works untouched.
     */
    public function getTenantSlugName(): string;
}
