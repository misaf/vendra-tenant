<?php

declare(strict_types=1);

use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;
use Misaf\VendraTenant\Services\DomainTenantFinder;

/*
 * Regression lock for the Traefik canonical-API migration.
 *
 * The reverse-proxy design adds a single canonical API host (api.<base>) and
 * keeps the admin/console/reseller panels on their own hosts, all pointing at
 * the same backend. This suite pins the host -> tenant behavior that the proxy
 * migration must NOT change:
 *   - platform hosts (api/console/reseller/apex) never resolve to a customer
 *     tenant purely from their hostname, so the canonical API cannot derive a
 *     tenant from Host (which is why a trusted mechanism is chosen later);
 *   - admin subdomains still resolve to their tenant;
 *   - registered customer storefront domains still resolve unchanged.
 */
beforeEach(function (): void {
    config()->set('app.url', 'https://vendra.test');
    config()->set('vendra-tenant.central_host', 'vendra.test');
});

it('does not resolve a customer tenant from platform hosts', function (string $host): void {
    // A live tenant exists, yet none of the platform hosts belong to it.
    Tenant::factory()->active()->create(['slug' => 'acme']);

    expect(app(DomainTenantFinder::class)->findForHost($host))->toBeNull();
})->with([
    'canonical api host' => 'api.vendra.test',
    'console host'       => 'console.vendra.test',
    'reseller host'      => 'reseller.vendra.test',
    'marketing apex'     => 'vendra.test',
]);

it('still resolves the tenant admin subdomain', function (): void {
    $tenant = Tenant::factory()->active()->create(['slug' => 'acme']);

    expect(app(DomainTenantFinder::class)->findForHost('acme.admin.vendra.test')?->getKey())
        ->toBe($tenant->getKey());
});

it('still resolves a registered customer storefront domain', function (): void {
    $tenant = Tenant::factory()->active()->create(['slug' => 'houshang']);
    TenantDomain::factory()->for($tenant)->create([
        'name'   => 'houshang-flowers.example.com',
        'active' => true,
    ]);

    expect(app(DomainTenantFinder::class)->findForHost('houshang-flowers.example.com')?->getKey())
        ->toBe($tenant->getKey());
});
