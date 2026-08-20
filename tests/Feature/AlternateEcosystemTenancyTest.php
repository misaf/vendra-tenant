<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Misaf\VendraTenant\Tests\Fixtures\Company;
use Misaf\VendraTenant\Tests\Fixtures\Document;
use Misaf\VendraTenant\Tests\Fixtures\StaticHostTenantFinder;
use Misaf\VendraTenant\Tests\Fixtures\Workspace;

/*
 | The rule this whole design rests on:
 |
 |     concrete tenant model name  !=  generic ownership column name
 |
 | `generic_documents` is written the way a reusable domain package writes a table — it
 | carries the neutral `tenant_id` and names no tenant. This suite runs it under
 | two entirely different tenant models, `Company` and `Workspace`, without
 | either one requiring a `company_id` or `workspace_id` column.
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

    Schema::create('generic_documents', function (Blueprint $table): void {
        $table->id();
        TenantSchema::addTenantColumn($table);
        $table->string('title');

        TenantSchema::addTenantIndex($table);
    });
});

afterEach(function (): void {
    Company::forgetCurrent();

    Schema::dropIfExists('generic_documents');
    Schema::dropIfExists('workspaces');
    Schema::dropIfExists('companies');

    TenantSchema::flushTenantColumnCache();
});

/**
 * @return array{0: Company, 1: Company}
 */
function twoCompanies(): array
{
    config()->set('vendra-tenant.model', Company::class);

    return [
        Company::query()->create(['name' => 'Acme', 'code' => 'acme']),
        Company::query()->create(['name' => 'Globex', 'code' => 'globex']),
    ];
}

it('owns a reusable package table through tenant_id, not company_id', function (): void {
    twoCompanies();

    expect(TenantSchema::column())->toBe('tenant_id')
        ->and(Schema::hasColumn('generic_documents', 'tenant_id'))->toBeTrue()
        ->and(Schema::hasColumn('generic_documents', 'company_id'))->toBeFalse()
        ->and(TenantSchema::hasTenantColumn('generic_documents'))->toBeTrue();
});

it('scopes a generic tenant_id table to the current Company', function (): void {
    [$acme, $globex] = twoCompanies();

    $resolver = app(TenantResolver::class);

    $acmeDocument = $resolver->execute(
        $acme->getKey(),
        fn(): Document => Document::query()->create(['title' => 'Acme brief']),
    );

    $globexDocument = $resolver->execute(
        $globex->getKey(),
        fn(): Document => Document::query()->create(['title' => 'Globex brief']),
    );

    // BelongsToTenant stamped the current Company without anyone passing an id.
    expect($acmeDocument->getAttribute('tenant_id'))->toBe($acme->getKey())
        ->and($globexDocument->getAttribute('tenant_id'))->toBe($globex->getKey());

    // Company A context sees only Company A documents.
    expect($resolver->execute($acme->getKey(), fn(): array => Document::query()->pluck('title')->all()))
        ->toBe(['Acme brief'])
        ->and($resolver->execute($globex->getKey(), fn(): array => Document::query()->pluck('title')->all()))
        ->toBe(['Globex brief'])
        ->and(Document::query()->withoutGlobalScopes()->count())->toBe(2);
});

it('resolves the owner relation to the configured Company', function (): void {
    [$acme] = twoCompanies();

    $document = app(TenantResolver::class)->execute(
        $acme->getKey(),
        fn(): Document => Document::query()->create(['title' => 'Acme brief']),
    );

    expect($document->tenant()->getForeignKeyName())->toBe('tenant_id')
        ->and($document->tenant)->toBeInstanceOf(Company::class)
        ->and($document->tenant->getKey())->toBe($acme->getKey());
});

it('runs the same generic table under a different tenant model unchanged', function (): void {
    /*
     | Same `documents` table, same `tenant_id` column, a Workspace instead of a
     | Company. Nothing about the table had to change, which is the point.
     */
    config()->set('vendra-tenant.model', Workspace::class);

    $first = Workspace::query()->create(['name' => 'First', 'handle' => 'first', 'active' => true]);
    $second = Workspace::query()->create(['name' => 'Second', 'handle' => 'second', 'active' => true]);

    $resolver = app(TenantResolver::class);

    $resolver->execute($first->getKey(), fn(): Document => Document::query()->create(['title' => 'First brief']));
    $resolver->execute($second->getKey(), fn(): Document => Document::query()->create(['title' => 'Second brief']));

    expect($resolver->modelClass())->toBe(Workspace::class)
        ->and(TenantSchema::column())->toBe('tenant_id')
        ->and($resolver->execute($first->getKey(), fn(): array => Document::query()->pluck('title')->all()))
        ->toBe(['First brief']);

    Workspace::forgetCurrent();
});

it('lets the application replace the host resolution port entirely', function (): void {
    [$acme] = twoCompanies();

    /*
     | No domains table, no `misaf/vendra-store`: a fixed host-to-tenant map is
     | a legitimate adapter, as would be a subdomain, header, or API-key one.
     */
    app()->instance(HostTenantFinder::class, new StaticHostTenantFinder('acme.internal', $acme));

    expect(app(HostTenantFinder::class)->findForHost('acme.internal')?->getKey())->toBe($acme->getKey())
        ->and(app(HostTenantFinder::class)->findForAdminHost('acme.internal')?->getKey())->toBe($acme->getKey())
        ->and(app(HostTenantFinder::class)->findForOrigin('https://acme.internal')?->getKey())->toBe($acme->getKey())
        ->and(app(HostTenantFinder::class)->findForHost('someone-else.internal'))->toBeNull();
});

it('builds a tenant-guarded generated column against the configured foreign key', function (): void {
    /*
     | Regression: every part of a tenant-scoped table must read the column name
     | from TenantSchema, raw SQL expressions included. A `default_guard` that
     | spelled `tenant_id` outright kept working under Vendra only by accident
     | and broke the moment the foreign key was configured to anything else.
     */
    config()->set('vendra-tenant.model', Company::class);
    config()->set('vendra-tenant.foreign_key', 'company_id');

    TenantSchema::flushTenantColumnCache();

    Schema::create('generic_guarded_documents', function (Blueprint $table): void {
        $table->id();
        TenantSchema::addTenantColumn($table);
        $table->boolean('is_default')->default(false);
        $table->unsignedBigInteger('default_guard')
            ->nullable()
            ->virtualAs(TenantSchema::enabled()
                ? 'CASE WHEN is_default THEN ' . TenantSchema::column() . ' ELSE NULL END'
                : 'CASE WHEN is_default THEN 1 ELSE NULL END');
    });

    expect(Schema::hasColumn('generic_guarded_documents', 'company_id'))->toBeTrue()
        ->and(Schema::hasColumn('generic_guarded_documents', 'tenant_id'))->toBeFalse();

    Schema::dropIfExists('generic_guarded_documents');
});
