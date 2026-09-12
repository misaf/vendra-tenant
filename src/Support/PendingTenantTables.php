<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Support;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraSupport\Tenancy\TenantTableRegistry;

/**
 * The read side of the tenancy retrofit: which registered tables still need
 * the tenant column. A plain query object rather than an action — it records
 * nothing and decides nothing, so callers reach it directly instead of going
 * through a pass-through action.
 */
final readonly class PendingTenantTables
{
    public function __construct(private TenantTableRegistry $tenantTables) {}

    /**
     * @return list<array{table: string, connection: ?string}>
     */
    public function list(): array
    {
        return array_values(array_filter(
            $this->tenantTables->all(),
            function (array $definition): bool {
                $schema = $this->schema(Arr::get($definition, 'connection'));

                return $schema->hasTable(Arr::get($definition, 'table'))
                    && $this->requiresRetrofit($schema, Arr::get($definition, 'table'));
            },
        ));
    }

    private function requiresRetrofit(Builder $schema, string $table): bool
    {
        return ! $schema->hasColumn($table, TenantSchema::column())
            || $this->tenantColumnIsNullable($schema, $table);
    }

    private function tenantColumnIsNullable(Builder $schema, string $table): bool
    {
        $foreignKey = TenantSchema::column();

        foreach ($schema->getColumns($table) as $column) {
            if ($foreignKey === Arr::get($column, 'name')) {
                return Arr::get($column, 'nullable');
            }
        }

        return false;
    }

    private function schema(?string $connection): Builder
    {
        return Schema::connection($connection);
    }
}
