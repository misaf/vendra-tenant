<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraTenant\Support\PendingTenantTables;

/**
 * Add the tenant foreign key to tables migrated before tenancy was installed.
 */
final readonly class EnableTenancyAction
{
    public function __construct(private PendingTenantTables $pendingTables) {}

    /**
     * @return array{tables: list<string>, updated_rows: int}
     */
    public function execute(int $tenantId): array
    {
        $tables = [];
        $updatedRows = 0;
        $foreignKey = TenantSchema::column();

        foreach ($this->pendingTables->list() as ['table' => $table, 'connection' => $connectionName]) {
            $schema = $this->schema($connectionName);
            $connection = $this->connection($connectionName);

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

    private function schema(?string $connection): Builder
    {
        return Schema::connection($connection);
    }

    private function tenantColumnIsNullable(Builder $schema, string $table): bool
    {
        $foreignKey = TenantSchema::column();

        foreach ($schema->getColumns($table) as ['name' => $name, 'nullable' => $nullable]) {
            if ($name === $foreignKey) {
                return $nullable;
            }
        }

        return false;
    }

    private function connection(?string $connection): Connection
    {
        return DB::connection($connection);
    }
}
