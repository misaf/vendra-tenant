<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Misaf\VendraTenant\Actions\ReplaceTenantDomainAction;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('activates a new domain and retains the previous one as trashed history', function (): void {
    $property = Tenant::factory()->create();
    $original = TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'status' => true]);

    $new = app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    expect($new->name)->toBe('new.test')
        ->and($new->status)->toBeTrue()
        ->and($new->trashed())->toBeFalse();

    $previous = TenantDomain::withoutGlobalScopes()->withTrashed()->find($original->getKey());

    expect($previous?->trashed())->toBeTrue()
        ->and($previous?->status)->toBeFalse();

    // Only one active, non-trashed domain resolves the property.
    expect($property->execute(fn() => $property->tenantDomains()->where('status', true)->count()))->toBe(1);
});

it('replaces the active domain even when another tenant is current', function (): void {
    $current = Tenant::factory()->create();
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'status' => true]);

    switchToTestTenant($current);

    $new = app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    expect($new->tenant_id)->toBe($property->getKey())
        ->and($property->execute(fn() => $property->tenantDomains()->where('status', true)->value('name')))->toBe('new.test');
});

it('keeps replaced history when the property is soft-deleted and restored', function (): void {
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'status' => true]);
    app(ReplaceTenantDomainAction::class)->execute($property, 'new.test');

    $property->delete();
    $property->restore();

    // The active domain resolves again; the replaced one stays trashed history.
    expect($property->execute(fn() => $property->tenantDomains()->where('status', true)->value('name')))->toBe('new.test')
        ->and($property->execute(fn() => $property->tenantDomains()->onlyTrashed()->count()))->toBe(1);
});

it('rejects a domain already active on another property', function (): void {
    $property = Tenant::factory()->create();
    TenantDomain::factory()->for($property)->create(['name' => 'old.test', 'status' => true]);

    $otherProperty = Tenant::factory()->create();
    TenantDomain::factory()->for($otherProperty)->create(['name' => 'taken.test', 'status' => true]);

    app(ReplaceTenantDomainAction::class)->execute($property, 'taken.test');
})->throws(ValidationException::class);
