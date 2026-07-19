<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Support\TenantSchema;
use Misaf\VendraSupport\Support\TenantTableRegistry;

final class EnableTenancyAction
{
    public function __construct(private readonly TenantTableRegistry $tenantTables) {}

    /**
     * @return list<array{table: string, connection: ?string}>
     */
    public function pendingTables(): array
    {
        return array_values(array_filter(
            $this->tenantTables->all(),
            function (array $definition): bool {
                $schema = $this->schema($definition['connection']);

                return $schema->hasTable($definition['table'])
                    && $this->requiresRetrofit($schema, $definition['table']);
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

        foreach ($this->pendingTables() as $definition) {
            $table = $definition['table'];
            $schema = $this->schema($definition['connection']);
            $connection = $this->connection($definition['connection']);

            if ( ! $schema->hasColumn($table, 'tenant_id')) {
                $schema->table($table, function (Blueprint $blueprint): void {
                    $blueprint->unsignedBigInteger('tenant_id')->nullable();
                });
            }

            $updatedRows += $connection->table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);

            if ( ! $schema->hasIndex($table, ['tenant_id'])) {
                $schema->table($table, function (Blueprint $blueprint): void {
                    $blueprint->index('tenant_id');
                });
            }

            if ($this->tenantColumnIsNullable($schema, $table)) {
                $schema->table($table, function (Blueprint $blueprint): void {
                    $blueprint->unsignedBigInteger('tenant_id')->nullable(false)->change();
                });
            }

            TenantSchema::forgetTenantColumn($table);
            $tables[] = $table;
        }

        return [
            'tables'       => $tables,
            'updated_rows' => $updatedRows,
        ];
    }

    private function requiresRetrofit(Builder $schema, string $table): bool
    {
        return ! $schema->hasColumn($table, 'tenant_id') || $this->tenantColumnIsNullable($schema, $table);
    }

    private function tenantColumnIsNullable(Builder $schema, string $table): bool
    {
        foreach ($schema->getColumns($table) as $column) {
            if ('tenant_id' === $column['name']) {
                return $column['nullable'];
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
