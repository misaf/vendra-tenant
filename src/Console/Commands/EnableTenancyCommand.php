<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Actions\EnableTenancyAction;

#[Description('Retrofit tenant ownership onto tables migrated before Vendra Tenant was installed')]
#[Signature('vendra-tenant:enable
        {tenant : Tenant ID or slug that will own existing unscoped records}
        {--force : Run without confirmation}')]
final class EnableTenancyCommand extends Command
{
    public function __construct(
        private readonly EnableTenancyAction $enableTenancyAction,
        private readonly TenantResolver $tenantResolver,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenant = $this->tenantResolver->findByKeyOrSlug((string) $this->argument('tenant'));

        if ($tenant === null) {
            $this->error('The requested tenant could not be found.');

            return self::FAILURE;
        }

        $tenantKey = $tenant->getKey();

        if (! is_int($tenantKey) && (! is_string($tenantKey) || ! ctype_digit($tenantKey))) {
            $this->error('The requested tenant has an unsupported key type.');

            return self::FAILURE;
        }

        $tables = $this->enableTenancyAction->pendingTables();

        if ($tables === []) {
            $this->info('All registered tables are already tenant-aware.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            sprintf(
                'Add tenant ownership to %d table(s) and assign existing unscoped records to tenant [%s]?',
                count($tables),
                $tenantKey,
            ),
        )) {
            $this->warn('Tenancy retrofit cancelled.');

            return self::FAILURE;
        }

        $result = $this->enableTenancyAction->execute((int) $tenantKey);

        $this->info(sprintf(
            'Enabled tenancy for %d table(s) and assigned %d existing record(s) to tenant [%s].',
            count(Arr::get($result, 'tables')),
            Arr::get($result, 'updated_rows'),
            $tenantKey,
        ));

        return self::SUCCESS;
    }
}
