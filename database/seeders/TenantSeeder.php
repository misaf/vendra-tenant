<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

final class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name'        => 'Misaf Shops',
            'description' => 'misaf tenant for shopping platform',
            'slug'        => 'misaf-shops',
            'status'      => true,
        ]);

        TenantDomain::factory()->create([
            'tenant_id'   => $tenant->id,
            'name'        => 'panel.ecommerce.test',
            'description' => 'Main domain for Misaf Shops tenant',
            'slug'        => 'panel-ecommerce-test',
            'status'      => true,
        ]);
    }
}
