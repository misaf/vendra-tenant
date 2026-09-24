<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Support;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraSupport\Tenancy\TenantTableRegistry;

final readonly class PendingTenantTables
{
    public function __construct(private TenantTableRegistry $tenantTables) {}

    /**
     * @return list<array{table: string, connection: ?string}>
     */
    public function list(): array
    {
        $pending = [];

        foreach ($this->tenantTables->all() as $definition) {
            ['table' => $table, 'connection' => $connection] = $definition;
            $schema = $this->schema($connection);

            if ($schema->hasTable($table) && $this->requiresRetrofit($schema, $table)) {
                $pending[] = $definition;
            }
        }

        return $pending;
    }

    private function requiresRetrofit(Builder $schema, string $table): bool
    {
        return ! $schema->hasColumn($table, TenantSchema::column())
            || $this->tenantColumnIsNullable($schema, $table);
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

    private function schema(?string $connection): Builder
    {
        return Schema::connection($connection);
    }
}
