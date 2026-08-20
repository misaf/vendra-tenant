<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Misaf\VendraSupport\Context\RequestJobContext;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Support\ConfiguredTenantResolver;
use Misaf\VendraTenant\Tasks\SwitchAppTask;
use Misaf\VendraTenant\Tests\Fixtures\Workspace;

beforeEach(function (): void {
    config()->set('vendra-tenant.model', Workspace::class);

    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->boolean('active')->default(true);
    });
});

afterEach(function (): void {
    Workspace::forgetCurrent();
    Schema::dropIfExists('workspaces');
});

function currentWorkspace(string $slug = 'acme'): Workspace
{
    return Workspace::query()->create(['name' => ucfirst($slug), 'slug' => $slug, 'active' => true]);
}

it('tracks the current tenant across request and job context switches', function (): void {
    $workspace = currentWorkspace();

    expect(Context::has(RequestJobContext::TENANT_ID))->toBeFalse();

    $workspace->execute(function () use ($workspace): void {
        expect(RequestJobContext::current()->tenantId)->toBe($workspace->getKey());
    });

    expect(Context::has(RequestJobContext::TENANT_ID))->toBeFalse();
});

it('throws when the tenant cannot be resolved for execution', function (): void {
    expect(fn(): mixed => (new ConfiguredTenantResolver())->execute(999999, fn(): null => null))
        ->toThrow(RuntimeException::class);
});

it('reports itself as the available tenant provider', function (): void {
    expect(app(TenantResolver::class))->toBeInstanceOf(ConfiguredTenantResolver::class)
        ->and(app(TenantResolver::class)->available())->toBeTrue();
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
    $task->makeCurrent(currentWorkspace());

    expect(asset('css/filament/filament/app.css'))->toBe('https://seomasters.test/css/filament/filament/app.css')
        ->and(Storage::disk('public')->url('fonts/inter.woff2'))->toBe('/storage/fonts/inter.woff2');

    $task->forgetCurrent();

    expect(asset('css/filament/filament/app.css'))->toBe('https://vendra.test/css/filament/filament/app.css')
        ->and(Storage::disk('public')->url('fonts/inter.woff2'))->toBe('/storage/fonts/inter.woff2');
});
