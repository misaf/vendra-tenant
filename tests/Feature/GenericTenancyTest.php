<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantAwareness;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraTenant\Contracts\TenantContract;
use Misaf\VendraTenant\Support\ConfiguredTenantResolver;
use Misaf\VendraTenant\Tests\Fixtures\Workspace;
use Misaf\VendraTenant\Tests\Fixtures\WorkspaceDocument;

/*
 | The engine is exercised through a tenant that is not Vendra's Store, is not
 | called Tenant, and whose foreign key is not `tenant_id`. Everything below
 | would fail the moment the package started assuming any of the three.
 */
beforeEach(function (): void {
    config()->set('vendra-tenant.model', Workspace::class);
    config()->set('vendra-tenant.foreign_key', 'workspace_id');
    TenantSchema::flushTenantColumnCache();

    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id('uuid');
        $table->string('name');
        $table->string('handle');
        $table->boolean('active')->default(true);
    });

    Schema::create('workspace_documents', function (Blueprint $table): void {
        $table->id();
        TenantSchema::addTenantColumn($table);
        $table->string('title');

        TenantSchema::addTenantIndex($table);
    });
});

afterEach(function (): void {
    Workspace::forgetCurrent();

    Schema::dropIfExists('workspace_documents');
    Schema::dropIfExists('workspaces');

    TenantSchema::flushTenantColumnCache();
});

function makeWorkspace(string $handle): Workspace
{
    return Workspace::query()->create([
        'name'   => ucfirst($handle),
        'handle' => $handle,
        'active' => true,
    ]);
}

it('resolves whichever tenant model the application configures', function (): void {
    $resolver = app(TenantResolver::class);

    expect($resolver)->toBeInstanceOf(ConfiguredTenantResolver::class)
        ->and($resolver->modelClass())->toBe(Workspace::class)
        ->and($resolver->available())->toBeTrue()
        ->and(TenantAwareness::enabled())->toBeTrue();
});

it('rejects a configured model that is not a tenant', function (): void {
    config()->set('vendra-tenant.model', WorkspaceDocument::class);

    app(TenantResolver::class)->modelClass();
})->throws(InvalidArgumentException::class);

it('works with a tenant model that is not named Tenant', function (): void {
    $workspace = makeWorkspace('acme');

    expect($workspace)->toBeInstanceOf(TenantContract::class)
        ->and($workspace->getTenantKey())->toBe($workspace->uuid)
        ->and($workspace->getTenantName())->toBe('Acme')
        ->and($workspace->getTenantSlug())->toBe('acme');
});

it('establishes and restores the tenant context', function (): void {
    $workspace = makeWorkspace('acme');
    $resolver = app(TenantResolver::class);

    expect($resolver->current())->toBeNull();

    $seen = $resolver->execute($workspace->getKey(), fn(): mixed => $resolver->currentId());

    expect($seen)->toBe($workspace->getKey())
        ->and($resolver->current())->toBeNull();

    $resolver->makeCurrent($workspace);

    expect($resolver->current()?->getKey())->toBe($workspace->getKey());
});

it('finds the configured tenant by key or slug', function (): void {
    $workspace = makeWorkspace('acme');

    $resolver = app(TenantResolver::class);

    expect($resolver->findByKeyOrSlug($workspace->getKey())?->getKey())->toBe($workspace->getKey())
        ->and($resolver->findByKeyOrSlug('acme')?->getKey())->toBe($workspace->getKey())
        ->and($resolver->findByKeyOrSlug('nope'))->toBeNull();
});

it('reads the tenant foreign key from configuration rather than assuming tenant_id', function (): void {
    expect(TenantSchema::column())->toBe('workspace_id')
        ->and(Schema::hasColumn('workspace_documents', 'workspace_id'))->toBeTrue()
        ->and(Schema::hasColumn('workspace_documents', 'tenant_id'))->toBeFalse()
        ->and(TenantSchema::hasTenantColumn('workspace_documents'))->toBeTrue()
        ->and(TenantSchema::tenantIndex(['title']))->toBe(['workspace_id', 'title']);
});

it('stamps and scopes records through the configured foreign key', function (): void {
    $first = makeWorkspace('first');
    $second = makeWorkspace('second');

    app(TenantResolver::class)->execute($first->getKey(), function (): void {
        WorkspaceDocument::query()->create(['title' => 'First brief']);
    });

    app(TenantResolver::class)->execute($second->getKey(), function (): void {
        WorkspaceDocument::query()->create(['title' => 'Second brief']);
    });

    expect(WorkspaceDocument::query()->withoutGlobalScopes()->count())->toBe(2)
        ->and(WorkspaceDocument::query()->withoutGlobalScopes()->pluck('workspace_id')->all())
        ->toBe([$first->getKey(), $second->getKey()]);

    $visible = app(TenantResolver::class)->execute(
        $first->getKey(),
        fn(): array => WorkspaceDocument::query()->pluck('title')->all(),
    );

    expect($visible)->toBe(['First brief']);
});

it('points the owner relation at the configured model and foreign key', function (): void {
    $workspace = makeWorkspace('acme');

    $document = app(TenantResolver::class)->execute(
        $workspace->getKey(),
        fn(): WorkspaceDocument => WorkspaceDocument::query()->create(['title' => 'Brief']),
    );

    expect($document)->toBeInstanceOf(WorkspaceDocument::class)
        ->and($document->tenant()->getForeignKeyName())->toBe('workspace_id')
        ->and($document->tenant)->toBeInstanceOf(Workspace::class)
        ->and($document->tenant->getKey())->toBe($workspace->getKey());
});

it('runs a callback once inside every tenant', function (): void {
    $first = makeWorkspace('first');
    $second = makeWorkspace('second');

    $seen = [];

    app(TenantResolver::class)->eachTenant(function () use (&$seen): void {
        $seen[] = app(TenantResolver::class)->currentId();
    });

    expect($seen)->toEqualCanonicalizing([$first->getKey(), $second->getKey()])
        ->and(app(TenantResolver::class)->current())->toBeNull();
});

it('offers only accessible tenants as search options', function (): void {
    $active = makeWorkspace('acme');
    Workspace::query()->create(['name' => 'Archived', 'handle' => 'archived', 'active' => false]);

    expect(app(TenantResolver::class)->searchOptions(''))->toBe([$active->getKey() => 'acme'])
        ->and(app(TenantResolver::class)->searchOptions('arch'))->toBe([]);
});
