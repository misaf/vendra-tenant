<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Misaf\VendraTenant\Actions\ReplaceTenantDomainAction;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('activates a new domain and retains the previous one as trashed history', function (): void {
    $property = Tenant::factory()->create();
    $original = TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'active' => true]);

    $new = app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    expect($new->name)->toBe('new.test')
        ->and($new->active)->toBeTrue()
        ->and($new->trashed())->toBeFalse();

    $previous = TenantDomain::withoutGlobalScopes()->withTrashed()->find($original->getKey());

    expect($previous?->trashed())->toBeTrue()
        ->and($previous?->active)->toBeFalse();

    // Only one active, non-trashed domain resolves the property.
    expect($property->execute(fn() => $property->tenantDomains()->where('active', true)->count()))->toBe(1);
});

it('replaces the active domain even when another tenant is current', function (): void {
    $current = Tenant::factory()->create();
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'active' => true]);

    switchToTestTenant($current);

    $new = app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    expect($new->tenant_id)->toBe($property->getKey())
        ->and($property->execute(fn() => $property->tenantDomains()->where('active', true)->value('name')))->toBe('new.test');
});

it('keeps replaced history when the property is soft-deleted and restored', function (): void {
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'active' => true]);
    app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    $property->delete();
    $property->restore();

    // The active domain resolves again; the replaced one stays trashed history.
    expect($property->execute(fn() => $property->tenantDomains()->where('active', true)->value('name')))->toBe('new.test')
        ->and($property->execute(fn() => $property->tenantDomains()->onlyTrashed()->count()))->toBe(1);
});

it('rejects a domain already active on another property', function (): void {
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'active' => true]);

    $otherProperty = Tenant::factory()->create();
    TenantDomain::factory()->for($otherProperty)->create(['name' => 'taken.test', 'active' => true]);

    app(ReplaceTenantDomainAction::class)->execute($property, 'taken.test');
})->throws(ValidationException::class);
