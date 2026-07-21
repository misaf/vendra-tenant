<?php

declare(strict_types=1);

use Misaf\VendraTenant\Actions\ReplaceTenantDomainAction;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('activates a new domain and retains the previous one as trashed history', function (): void {
    $website = Tenant::factory()->create();
    $original = TenantDomain::factory()->for($website)->create(['name' => 'old.test', 'status' => true]);

    $new = app(ReplaceTenantDomainAction::class)->execute($website, 'new.test');

    expect($new->name)->toBe('new.test')
        ->and($new->status)->toBeTrue()
        ->and($new->trashed())->toBeFalse();

    $previous = TenantDomain::withoutGlobalScopes()->withTrashed()->find($original->getKey());

    expect($previous?->trashed())->toBeTrue()
        ->and($previous?->status)->toBeFalse();

    // Only one active, non-trashed domain resolves the website.
    expect($website->execute(fn() => $website->tenantDomains()->where('status', true)->count()))->toBe(1);
});

it('replaces the active domain even when another tenant is current', function (): void {
    $current = Tenant::factory()->create();
    $website = Tenant::factory()->create();
    TenantDomain::factory()->for($website)->create(['name' => 'old.test', 'status' => true]);

    switchToTestTenant($current);

    $new = app(ReplaceTenantDomainAction::class)->execute($website, 'new.test');

    expect($new->tenant_id)->toBe($website->getKey())
        ->and($website->execute(fn() => $website->tenantDomains()->where('status', true)->value('name')))->toBe('new.test');
});

it('keeps replaced history when the website is soft-deleted and restored', function (): void {
    $website = Tenant::factory()->create();
    TenantDomain::factory()->for($website)->create(['name' => 'old.test', 'status' => true]);
    app(ReplaceTenantDomainAction::class)->execute($website, 'new.test');

    $website->delete();
    $website->restore();

    // The active domain resolves again; the replaced one stays trashed history.
    expect($website->execute(fn() => $website->tenantDomains()->where('status', true)->value('name')))->toBe('new.test')
        ->and($website->execute(fn() => $website->tenantDomains()->onlyTrashed()->count()))->toBe(1);
});
