<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraTenant\Contracts\TenantContract;
use Misaf\VendraTenant\Tests\Fixtures\Company;
use Misaf\VendraTenant\Tests\Fixtures\Workspace;
use Spatie\Multitenancy\Contracts\IsTenant;

/*
 | The resolver used to spell `id` and `slug` into its own SQL, which quietly
 | made those two column names part of the contract. It now asks the model:
 | Eloquent's `getKeyName()` for the key, `TenantContract::getTenantSlugName()`
 | for the slug. Every tenant below runs through the *same* resolver code.
 |
 |     Company    company_id + code
 |     Workspace  uuid       + handle
 |
 | Store's `id` + `slug` is the third case; it lives in `misaf/vendra-store`
 | (`StoreAsTenantTest`) because this package must never import a Store.
 */
beforeEach(function (): void {
    config()->set('vendra-tenant.foreign_key', TenantSchema::DEFAULT_FOREIGN_KEY);

    TenantSchema::flushTenantColumnCache();

    Schema::create('companies', function (Blueprint $table): void {
        $table->id('company_id');
        $table->string('name');
        $table->string('code');
    });

    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id('uuid');
        $table->string('name');
        $table->string('handle');
        $table->boolean('active')->default(true);
    });
});

afterEach(function (): void {
    /*
     | Forget through whichever model the test configured. Spatie types
     | `current()` as `?static`, so calling `Company::forgetCurrent()` while a
     | Workspace is current is a TypeError rather than a no-op.
     */
    $modelClass = config('vendra-tenant.model');

    if (is_string($modelClass) && is_a($modelClass, IsTenant::class, true)) {
        $modelClass::forgetCurrent();
    }

    Schema::dropIfExists('workspaces');
    Schema::dropIfExists('companies');

    TenantSchema::flushTenantColumnCache();
});

dataset('tenant column namings', [
    'Company keyed by company_id, slugged by code' => [Company::class, 'company_id', 'code'],
    'Workspace keyed by uuid, slugged by handle'   => [Workspace::class, 'uuid', 'handle'],
]);

/**
 * @param class-string<Model&TenantContract> $modelClass
 */
function makeNamedTenant(string $modelClass, string $slug, bool $active = true): Model&TenantContract
{
    /** @var Model&TenantContract $tenant */
    $tenant = new $modelClass();

    $attributes = [
        'name'                        => ucfirst($slug),
        $tenant->getTenantSlugName()  => $slug,
    ];

    if (Schema::hasColumn($tenant->getTable(), 'active')) {
        $attributes['active'] = $active;
    }

    return $modelClass::query()->create($attributes);
}

it('reports its own key and slug columns instead of the engine assuming them', function (
    string $modelClass,
    string $keyName,
    string $slugName,
): void {
    config()->set('vendra-tenant.model', $modelClass);

    $tenant = makeNamedTenant($modelClass, 'acme');

    expect($tenant->getKeyName())->toBe($keyName)
        ->and($tenant->getTenantSlugName())->toBe($slugName)
        ->and($tenant->getTenantSlug())->toBe('acme')
        ->and($tenant->getTenantName())->toBe('Acme')
        ->and($tenant->getTenantKey())->toBe($tenant->getKey())
        // The engine's own column names are nowhere on this model.
        ->and(Schema::hasColumn($tenant->getTable(), 'slug'))->toBeFalse();
})->with('tenant column namings');

it('finds a tenant by its primary key whatever that key is called', function (
    string $modelClass,
    string $keyName,
): void {
    config()->set('vendra-tenant.model', $modelClass);

    $tenant = makeNamedTenant($modelClass, 'acme');

    $found = app(TenantResolver::class)->findByKeyOrSlug($tenant->getKey());

    expect($found)->toBeInstanceOf($modelClass)
        ->and($found?->getKey())->toBe($tenant->getKey())
        ->and($found?->getKeyName())->toBe($keyName);
})->with('tenant column namings');

it('finds a tenant by its slug whatever that column is called', function (
    string $modelClass,
): void {
    config()->set('vendra-tenant.model', $modelClass);

    $acme = makeNamedTenant($modelClass, 'acme');
    makeNamedTenant($modelClass, 'globex');

    $resolver = app(TenantResolver::class);

    expect($resolver->findByKeyOrSlug('acme')?->getKey())->toBe($acme->getKey())
        ->and($resolver->findByKeyOrSlug('globex')?->getKey())->not->toBe($acme->getKey())
        ->and($resolver->findByKeyOrSlug('nobody'))->toBeNull();
})->with('tenant column namings');

it('builds search options from the model-declared key and slug columns', function (
    string $modelClass,
): void {
    config()->set('vendra-tenant.model', $modelClass);

    $acme = makeNamedTenant($modelClass, 'acme');

    $resolver = app(TenantResolver::class);

    expect($resolver->searchOptions(''))->toBe([$acme->getTenantKey() => 'acme'])
        ->and($resolver->searchOptions('acm'))->toBe([$acme->getTenantKey() => 'acme'])
        ->and($resolver->searchOptions('zzz'))->toBe([]);
})->with('tenant column namings');

it('establishes the current tenant through the generic resolver', function (
    string $modelClass,
): void {
    config()->set('vendra-tenant.model', $modelClass);

    $acme = makeNamedTenant($modelClass, 'acme');
    $globex = makeNamedTenant($modelClass, 'globex');

    $resolver = app(TenantResolver::class);

    expect($resolver->current())->toBeNull();

    // By model, by key, and by slug — all three entry points.
    expect($resolver->makeCurrent($acme))->toBeTrue()
        ->and($resolver->currentId())->toBe($acme->getTenantKey());

    expect($resolver->makeCurrent($globex->getKey()))->toBeTrue()
        ->and($resolver->currentId())->toBe($globex->getTenantKey());

    expect($resolver->makeCurrent('acme'))->toBeTrue()
        ->and($resolver->currentId())->toBe($acme->getTenantKey());

    $seen = $resolver->execute($globex->getKey(), fn(): ?int => $resolver->currentId());

    expect($seen)->toBe($globex->getTenantKey())
        ->and($resolver->currentId())->toBe($acme->getTenantKey());
})->with('tenant column namings');
