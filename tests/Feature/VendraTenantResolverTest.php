<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Misaf\VendraSupport\Context\RequestJobContext;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Support\VendraTenantResolver;
use Misaf\VendraTenant\Tasks\SwitchAppTask;

it('runs the callback in the tenant context and restores the previous context', function (): void {
    $tenant = Tenant::factory()->active()->create();

    expect(Tenant::current())->toBeNull();

    $result = (new VendraTenantResolver())->execute(
        $tenant->getKey(),
        fn(): mixed => Tenant::current()?->getKey(),
    );

    expect($result)->toBe($tenant->getKey())
        ->and(Tenant::current())->toBeNull();
});

it('tracks the current tenant across request and job context switches', function (): void {
    $tenant = Tenant::factory()->active()->create();

    expect(Context::has(RequestJobContext::TENANT_ID))->toBeFalse();

    $tenant->execute(function () use ($tenant): void {
        expect(RequestJobContext::current()->tenantId)->toBe($tenant->getKey());
    });

    expect(Context::has(RequestJobContext::TENANT_ID))->toBeFalse();
});

it('throws when the tenant cannot be resolved for execution', function (): void {
    expect(fn(): mixed => (new VendraTenantResolver())->execute(999999, fn(): null => null))
        ->toThrow(RuntimeException::class);
});

it('runs the callback within every tenant context and restores the previous one', function (): void {
    $first = Tenant::factory()->active()->create();
    $second = Tenant::factory()->active()->create();

    expect(Tenant::current())->toBeNull();

    $seen = [];

    (new VendraTenantResolver())->eachTenant(function () use (&$seen): void {
        $seen[] = Tenant::current()?->getKey();
    });

    expect($seen)->toEqualCanonicalizing([$first->getKey(), $second->getKey()])
        ->and(Tenant::current())->toBeNull();
});

it('offers only active tenants as search options', function (): void {
    $active = Tenant::factory()->active()->create(['slug' => 'acme-shop']);
    Tenant::factory()->inactive()->create(['slug' => 'acme-archive']);
    Tenant::factory()->active()->create([
        'slug'                 => 'acme-billing-suspended',
        'billing_suspended_at' => now(),
    ]);
    $other = Tenant::factory()->active()->create(['slug' => 'globex']);

    $resolver = new VendraTenantResolver();

    expect($resolver->searchOptions(''))->toBe([
        $active->getKey() => 'acme-shop',
        $other->getKey()  => 'globex',
    ])
        ->and($resolver->searchOptions('acme'))->toBe([$active->getKey() => 'acme-shop']);
});

it('uses the current tenant domain as the asset origin', function (): void {
    Config::set('app.url', 'https://vendra.test');
    Config::set('app.asset_url', 'https://vendra.test');
    Config::set('filesystems.disks.public.url', '/storage');
    URL::useOrigin('https://vendra.test');
    URL::useAssetOrigin('https://vendra.test');

    expect(Storage::disk('public')->url('fonts/inter.woff2'))->toBe('/storage/fonts/inter.woff2');

    $this->app->instance('request', Request::create('https://seomasters.test/reseller'));

    $task = new SwitchAppTask();
    $task->makeCurrent(Tenant::factory()->active()->create());

    expect(asset('css/filament/filament/app.css'))->toBe('https://seomasters.test/css/filament/filament/app.css')
        ->and(Storage::disk('public')->url('fonts/inter.woff2'))->toBe('/storage/fonts/inter.woff2');

    $task->forgetCurrent();

    expect(asset('css/filament/filament/app.css'))->toBe('https://vendra.test/css/filament/filament/app.css')
        ->and(Storage::disk('public')->url('fonts/inter.woff2'))->toBe('/storage/fonts/inter.woff2');
});
