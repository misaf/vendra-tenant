<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Support\VendraTenantResolver;

it('runs the callback in the tenant context and restores the previous context', function (): void {
    $tenant = Tenant::factory()->enabled()->create();

    expect(Tenant::current())->toBeNull();

    $result = (new VendraTenantResolver())->execute(
        $tenant->getKey(),
        fn(): mixed => Tenant::current()?->getKey(),
    );

    expect($result)->toBe($tenant->getKey())
        ->and(Tenant::current())->toBeNull();
});

it('throws when the tenant cannot be resolved for execution', function (): void {
    expect(fn(): mixed => (new VendraTenantResolver())->execute(999999, fn(): null => null))
        ->toThrow(RuntimeException::class);
});

it('runs the callback within every tenant context and restores the previous one', function (): void {
    $first = Tenant::factory()->enabled()->create();
    $second = Tenant::factory()->enabled()->create();

    expect(Tenant::current())->toBeNull();

    $seen = [];

    (new VendraTenantResolver())->eachTenant(function () use (&$seen): void {
        $seen[] = Tenant::current()?->getKey();
    });

    expect($seen)->toEqualCanonicalizing([$first->getKey(), $second->getKey()])
        ->and(Tenant::current())->toBeNull();
});

it('offers only enabled tenants as search options', function (): void {
    $enabled = Tenant::factory()->enabled()->create(['slug' => 'acme-shop']);
    Tenant::factory()->disabled()->create(['slug' => 'acme-archive']);
    $other = Tenant::factory()->enabled()->create(['slug' => 'globex']);

    $resolver = new VendraTenantResolver();

    expect($resolver->searchOptions(''))->toBe([
        $enabled->getKey() => 'acme-shop',
        $other->getKey()   => 'globex',
    ])
        ->and($resolver->searchOptions('acme'))->toBe([$enabled->getKey() => 'acme-shop']);
});
