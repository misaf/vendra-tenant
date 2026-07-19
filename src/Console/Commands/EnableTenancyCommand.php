<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Illuminate\Console\Command;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Actions\EnableTenancyAction;

final class EnableTenancyCommand extends Command
{
    protected $signature = 'vendra-tenant:enable
        {tenant : Tenant ID or slug that will own existing unscoped records}
        {--force : Run without confirmation}';

    protected $description = 'Retrofit tenant ownership onto tables migrated before Vendra Tenant was installed';

    public function __construct(
        private readonly EnableTenancyAction $enableTenancyAction,
        private readonly TenantResolver $tenantResolver,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenant = $this->tenantResolver->findByKeyOrSlug((string) $this->argument('tenant'));

        if (null === $tenant) {
            $this->error('The requested tenant could not be found.');

            return self::FAILURE;
        }

        $tenantKey = $tenant->getKey();

        if ( ! is_int($tenantKey) && ( ! is_string($tenantKey) || ! ctype_digit($tenantKey))) {
            $this->error('The requested tenant has an unsupported key type.');

            return self::FAILURE;
        }

        $tables = $this->enableTenancyAction->pendingTables();

        if ([] === $tables) {
            $this->info('All registered tables are already tenant-aware.');

            return self::SUCCESS;
        }

        if ( ! $this->option('force') && ! $this->confirm(
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
            count($result['tables']),
            $result['updated_rows'],
            $tenantKey,
        ));

        return self::SUCCESS;
    }
}
