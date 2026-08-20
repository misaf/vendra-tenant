<?php

declare(strict_types=1);

use Illuminate\Support\Uri;

return [
    /*
     * The concrete model that plays the tenant role. It must be an Eloquent
     * model implementing `Misaf\VendraTenant\Contracts\TenantContract` — the
     * Store in Vendra ecommerce, a Company, Workspace or Organization
     * elsewhere. The engine never names it itself, so an application that
     * installs a tenant domain package must point this at its own model.
     */
    'model' => null,

    /*
     * The foreign key tenant-scoped tables carry. Reusable, tenant-aware
     * packages share this one column, so it stays neutral: `products`,
     * `blog_posts` and `roles` are all owned through `tenant_id` regardless of
     * which model plays the tenant. An application that prefers to name the
     * column after its own tenant — `company_id`, `workspace_id` — sets it
     * here, and every schema helper, scope and relation follows.
     */
    'foreign_key' => 'tenant_id',

    /*
     * The platform's own host. Tenant administration surfaces live beneath it
     * as `<tenant slug>.admin.<central host>`.
     */
    'central_host' => Uri::of((string) env('APP_URL', 'http://localhost'))->host(),
];
