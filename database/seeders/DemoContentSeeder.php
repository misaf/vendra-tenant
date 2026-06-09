<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

final class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name'        => 'Vendra',
            'description' => 'vendra tenant for shopping platform',
            'slug'        => 'vendra',
            'status'      => true,
        ]);

        TenantDomain::factory()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'vendra.test',
            'description' => 'Main domain for vendra tenant',
            'slug'        => 'vendra-test',
            'status'      => true,
        ]);
    }
}
