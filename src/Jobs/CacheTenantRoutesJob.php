<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Misaf\VendraSupport\Context\RequestJobContext;
use RuntimeException;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Not tenant aware, since it passes `--tenant` and is dispatched without a current tenant.
 */
final class CacheTenantRoutesJob implements NotTenantAware, ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $tenantId) {}

    public function handle(): void
    {
        new RequestJobContext(
            traceId: RequestJobContext::resolveTraceId(),
            operation: 'tenant_route_cache',
            tenantId: $this->tenantId,
        )->scope(fn () => $this->cacheRoutes());
    }

    private function cacheRoutes(): void
    {
        $exitCode = Artisan::call('tenants:artisan', [
            'artisanCommand' => 'route:cache',
            '--tenant' => [$this->tenantId],
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf(
                'Tenant route cache command failed with exit code [%d].',
                $exitCode,
            ));
        }
    }
}
