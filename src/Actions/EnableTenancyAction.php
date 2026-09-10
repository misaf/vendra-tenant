<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Support\Arr;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraSupport\Tenancy\TenantTableRegistry;

/**
 * Retrofits the configured tenant foreign key onto tables that were migrated
 * before a tenant provider was installed. The column name is read from
 * {@see TenantSchema}, never assumed, so the same command retrofits `tenant_id`
 * here and `company_id` in a Company-tenanted application.
 */
final readonly class EnableTenancyAction
{
    public function __construct(private TenantTableRegistry $tenantTables) {}

    /**
     * @return list<array{table: string, connection: ?string}>
     */
    public function pendingTables(): array
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

    /**
     * @return array{tables: list<string>, updated_rows: int}
     */
    public function execute(int $tenantId): array
    {
        $tables = [];
        $updatedRows = 0;
        $foreignKey = TenantSchema::column();

        foreach ($this->pendingTables() as $definition) {
            $table = Arr::get($definition, 'table');
            $schema = $this->schema(Arr::get($definition, 'connection'));
            $connection = $this->connection(Arr::get($definition, 'connection'));

            if (! $schema->hasColumn($table, $foreignKey)) {
                $schema->table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                    $blueprint->unsignedBigInteger($foreignKey)->nullable();
                });
            }

            $updatedRows += $connection->table($table)
                ->whereNull($foreignKey)
                ->update([$foreignKey => $tenantId]);

            if (! $schema->hasIndex($table, [$foreignKey])) {
                $schema->table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                    $blueprint->index($foreignKey);
                });
            }

            if ($this->tenantColumnIsNullable($schema, $table)) {
                $schema->table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                    $blueprint->unsignedBigInteger($foreignKey)->nullable(false)->change();
                });
            }

            TenantSchema::forgetTenantColumn($table);
            $tables[] = $table;
        }

        return [
            'tables' => $tables,
            'updated_rows' => $updatedRows,
        ];
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

    private function connection(?string $connection): Connection
    {
        return DB::connection($connection);
    }
}
