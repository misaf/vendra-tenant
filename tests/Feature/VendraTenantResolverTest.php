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
