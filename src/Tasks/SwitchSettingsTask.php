<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Spatie\LaravelSettings\SettingsCache;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchSettingsTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        SettingsCache::resolvePrefixUsing(fn() => $tenant->id);
    }

    public function forgetCurrent(): void
    {
        SettingsCache::resolvePrefixUsing(null);
    }
}
