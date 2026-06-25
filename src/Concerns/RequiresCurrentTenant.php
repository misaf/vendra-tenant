<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Concerns;

use Misaf\VendraTenant\Models\Tenant;
use RuntimeException;

trait RequiresCurrentTenant
{
    protected function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if ( ! $tenant instanceof Tenant) {
            throw new RuntimeException(sprintf(
                '%s seeding requires a current tenant.',
                defined('static::MODULE_NAME') ? static::MODULE_NAME : static::class,
            ));
        }

        return $tenant;
    }
}
