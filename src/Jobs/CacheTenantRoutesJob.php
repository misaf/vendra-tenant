<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Regenerates a tenant's route cache off the request lifecycle.
 *
 * Tenant route sets can diverge, so each tenant gets its own cache. Running it
 * on a queue keeps provisioning responsive under load. It targets its tenant
 * explicitly via the `--tenant` option, so it opts out of Spatie's automatic
 * tenant-aware job binding (there is no current tenant when it is dispatched
 * from the platform panel).
 */
final class CacheTenantRoutesJob implements NotTenantAware, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $tenantId) {}

    public function handle(): void
    {
        $exitCode = Artisan::call('tenants:artisan', [
            'artisanCommand' => 'route:cache',
            '--tenant'       => [$this->tenantId],
        ]);

        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf(
                'Tenant route cache command failed with exit code [%d].',
                $exitCode,
            ));
        }
    }
}
