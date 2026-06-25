<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\search;

use Misaf\VendraSupport\Console\Commands\SeedCommand;

use Misaf\VendraTenant\Models\Tenant;

abstract class TenantSeedCommand extends SeedCommand
{
    private const int TENANT_SEARCH_LIMIT = 10;

    /**
     * @return array<string, callable>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'tenant' => fn() => search(
                label: 'Which tenant should receive seed data?',
                placeholder: 'Search tenant slug',
                options: fn(string $value): array => $this->tenantSearchOptions($value),
                hint: 'Seeders run only for the selected tenant.',
                scroll: self::TENANT_SEARCH_LIMIT,
            ),
            ...parent::promptForMissingArgumentsUsing(),
        ];
    }

    protected function prepareForSeeding(): bool
    {
        $tenantInput = (string) $this->argument('tenant');
        $tenant = $this->resolveTenant($tenantInput);

        if ( ! $tenant instanceof Tenant) {
            $this->error(sprintf('Tenant [%s] was not found.', $tenantInput));

            return false;
        }

        $tenant->makeCurrent();

        return true;
    }

    private function resolveTenant(string $tenant): ?Tenant
    {
        return Tenant::query()
            ->whereKey($tenant)
            ->orWhere('slug', $tenant)
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function tenantSearchOptions(string $value): array
    {
        $search = mb_trim($value);

        $tenants = Tenant::query()
            ->select(['id', 'slug'])
            ->when('' !== $search, fn(Builder $query): Builder => $query->where('slug', 'like', "%{$search}%"))
            ->limit(self::TENANT_SEARCH_LIMIT)
            ->get();

        $options = [];

        foreach ($tenants as $tenant) {
            $options[$tenant->id] = $tenant->slug;
        }

        return $options;
    }
}
