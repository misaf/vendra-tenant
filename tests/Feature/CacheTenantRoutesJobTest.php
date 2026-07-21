<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Misaf\VendraTenant\Jobs\CacheTenantRoutesJob;
use Spatie\Multitenancy\Jobs\NotTenantAware;

it('caches routes for the given tenant', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('tenants:artisan', [
            'artisanCommand' => 'route:cache',
            '--tenant'       => [7],
        ])
        ->andReturn(0);

    (new CacheTenantRoutesJob(7))->handle();
});

it('throws when the route cache command fails', function (): void {
    Artisan::shouldReceive('call')->once()->andReturn(1);

    (new CacheTenantRoutesJob(7))->handle();
})->throws(RuntimeException::class);

it('opts out of tenant-aware job binding', function (): void {
    expect(new CacheTenantRoutesJob(1))->toBeInstanceOf(NotTenantAware::class);
});
