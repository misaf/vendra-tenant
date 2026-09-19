<?php

declare(strict_types=1);

use Illuminate\Support\Uri;

return [
    /*
     * The Eloquent model that plays the tenant role, implementing
     * `Misaf\VendraTenant\Contracts\TenantContract`.
     */
    'model' => null,

    /*
     * The foreign key carried by every tenant-scoped table.
     */
    'foreign_key' => 'tenant_id',

    /*
     * The platform host; tenant admin panels live at `<slug>.admin.<central host>`.
     */
    'central_host' => Uri::of((string) env('APP_URL', 'http://localhost'))->host(),
];
