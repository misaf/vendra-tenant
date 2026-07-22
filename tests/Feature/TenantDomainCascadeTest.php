<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('soft-deletes a property domains even when another tenant is current', function (): void {
    $current = Tenant::factory()->create();
    $property = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($property)->create(['status' => true]);

    switchToTestTenant($current);

    $property->delete();

    $persisted = TenantDomain::withoutGlobalScopes()->withTrashed()->find($domain->getKey());

    expect($property->fresh()?->trashed())->toBeTrue()
        ->and($persisted?->trashed())->toBeTrue();
});

it('restores trashed domains when a property is restored', function (): void {
    $property = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($property)->create(['status' => true]);

    $property->delete();
    $property->restore();

    expect($property->fresh()?->trashed())->toBeFalse()
        ->and($property->execute(fn() => $property->tenantDomains()->whereKey($domain->getKey())->exists()))->toBeTrue();
});

it('permanently removes domains when a property is force-deleted', function (): void {
    $property = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($property)->create();

    $property->forceDelete();

    expect(Tenant::withTrashed()->whereKey($property->getKey())->exists())->toBeFalse()
        ->and(TenantDomain::withTrashed()->whereKey($domain->getKey())->exists())->toBeFalse();
});
