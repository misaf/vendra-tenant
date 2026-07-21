<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('soft-deletes a website domains even when another tenant is current', function (): void {
    $current = Tenant::factory()->create();
    $website = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($website)->create(['status' => true]);

    switchToTestTenant($current);

    $website->delete();

    $persisted = TenantDomain::withoutGlobalScopes()->withTrashed()->find($domain->getKey());

    expect($website->fresh()?->trashed())->toBeTrue()
        ->and($persisted?->trashed())->toBeTrue();
});

it('restores trashed domains when a website is restored', function (): void {
    $website = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($website)->create(['status' => true]);

    $website->delete();
    $website->restore();

    expect($website->fresh()?->trashed())->toBeFalse()
        ->and($website->execute(fn() => $website->tenantDomains()->whereKey($domain->getKey())->exists()))->toBeTrue();
});

it('permanently removes domains when a website is force-deleted', function (): void {
    $website = Tenant::factory()->create();
    $domain = TenantDomain::factory()->for($website)->create();

    $website->forceDelete();

    expect(Tenant::withTrashed()->whereKey($website->getKey())->exists())->toBeFalse()
        ->and(TenantDomain::withTrashed()->whereKey($domain->getKey())->exists())->toBeFalse();
});
