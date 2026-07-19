<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Support\TenantSchema;
use Misaf\VendraSupport\Support\TenantTableRegistry;
use Misaf\VendraTenant\Models\Tenant;

beforeEach(function (): void {
    Schema::create('legacy_tenant_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    app(TenantTableRegistry::class)->register('legacy_tenant_records');
});

afterEach(function (): void {
    Schema::dropIfExists('legacy_tenant_records');
});

it('retrofits and backfills tables migrated before tenancy was installed', function (): void {
    $tenant = Tenant::factory()->enabled()->create(['slug' => 'legacy-shop']);
    DB::table('legacy_tenant_records')->insert(['name' => 'Legacy record']);

    expect(TenantSchema::hasTenantColumn('legacy_tenant_records'))->toBeFalse();

    $this->artisan('vendra-tenant:enable', [
        'tenant'  => 'legacy-shop',
        '--force' => true,
    ])
        ->expectsOutputToContain('Enabled tenancy for 1 table(s) and assigned 1 existing record(s)')
        ->assertSuccessful();

    $tenantColumn = collect(Schema::getColumns('legacy_tenant_records'))
        ->firstWhere('name', 'tenant_id');

    expect(Schema::hasColumn('legacy_tenant_records', 'tenant_id'))->toBeTrue()
        ->and(Schema::hasIndex('legacy_tenant_records', ['tenant_id']))->toBeTrue()
        ->and(TenantSchema::hasTenantColumn('legacy_tenant_records'))->toBeTrue()
        ->and($tenantColumn['nullable'])->toBeFalse()
        ->and(DB::table('legacy_tenant_records')->value('tenant_id'))->toBe($tenant->getKey());
});

it('is idempotent after a table has been retrofitted', function (): void {
    $tenant = Tenant::factory()->enabled()->create();

    $arguments = [
        'tenant'  => (string) $tenant->getKey(),
        '--force' => true,
    ];

    $this->artisan('vendra-tenant:enable', $arguments)->assertSuccessful();

    $this->artisan('vendra-tenant:enable', $arguments)
        ->expectsOutput('All registered tables are already tenant-aware.')
        ->assertSuccessful();
});

it('does not mutate schemas when the target tenant does not exist', function (): void {
    $this->artisan('vendra-tenant:enable', [
        'tenant'  => 'missing-tenant',
        '--force' => true,
    ])
        ->expectsOutput('The requested tenant could not be found.')
        ->assertFailed();

    expect(Schema::hasColumn('legacy_tenant_records', 'tenant_id'))->toBeFalse();
});
