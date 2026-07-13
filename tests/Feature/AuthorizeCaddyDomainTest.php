<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

it('authorizes certificates for an enabled tenant domain from localhost', function (): void {
    $tenant = Tenant::factory()->enabled()->create();

    TenantDomain::factory()->for($tenant)->create([
        'name'   => 'shop.example.com',
        'status' => true,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->get('/caddy/domain-check?domain=shop.example.com')
        ->assertSuccessful();
});

it('rejects domains that are not eligible for certificates', function (Closure $createDomain, string $domain): void {
    $createDomain();

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->get('/caddy/domain-check?domain=' . urlencode($domain))
        ->assertNotFound();
})->with([
    'unknown domain'  => [fn(): null => null, 'unknown.example.com'],
    'invalid domain'  => [fn(): null => null, 'https://shop.example.com'],
    'disabled domain' => [function (): void {
        $tenant = Tenant::factory()->enabled()->create();

        TenantDomain::factory()->for($tenant)->create([
            'name'   => 'disabled.example.com',
            'status' => false,
        ]);
    }, 'disabled.example.com'],
    'disabled tenant' => [function (): void {
        $tenant = Tenant::factory()->disabled()->create();

        TenantDomain::factory()->for($tenant)->create([
            'name'   => 'inactive.example.com',
            'status' => true,
        ]);
    }, 'inactive.example.com'],
]);

it('does not expose certificate authorization publicly', function (): void {
    $tenant = Tenant::factory()->enabled()->create();

    TenantDomain::factory()->for($tenant)->create([
        'name'   => 'shop.example.com',
        'status' => true,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->get('/caddy/domain-check?domain=shop.example.com')
        ->assertNotFound();
});
