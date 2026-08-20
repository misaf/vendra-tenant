# Vendra Tenant

A generic multi-tenancy engine for Laravel applications, built on Spatie Laravel
Multitenancy.

**Tenant is a technical role, not a business entity.** This package owns the
mechanism — current tenant context, resolution, switching, isolation — and never
the model that plays the role. That model is yours: Vendra ecommerce points it at
a `Store`, another application points it at a `Company`, `Workspace`,
`Organization` or `Team`.

Installing this package activates tenant awareness across domain packages by
binding the shared `Misaf\VendraSupport\Contracts\TenantResolver` contract to
`ConfiguredTenantResolver`. Domain packages stay coupled only to
`misaf/vendra-support`, and this package depends on no business domain at all —
there is an architecture test that says so.

## Features

- A configurable tenant model, resolved from `vendra-tenant.model`
- A configurable tenant foreign key, so scoping never assumes `tenant_id`
- Establishing, switching and restoring the current tenant context
- Running a callback inside one tenant, or once inside every tenant
- Application and mail configuration switch tasks
- Per-tenant route caches
- A host-resolution port (`Contracts\HostTenantFinder`) the application implements
- Loopback-only Caddy on-demand TLS domain authorization
- A retrofit command for tables migrated before tenancy was enabled

## Requirements

- PHP 8.3+
- Laravel 13
- `misaf/vendra-support`
- `spatie/laravel-multitenancy`

## Installation

```bash
composer require misaf/vendra-tenant
php artisan vendor:publish --tag=vendra-tenant-config
```

## Choosing the tenant model

Your tenant is any Eloquent model that implements
`Misaf\VendraTenant\Contracts\TenantContract`. Extending Spatie's base tenant is
the shortest route to the `IsTenant` half of the contract, and the
`IsTenantModel` concern supplies the rest from `name` and `slug` columns:

```php
use Misaf\VendraTenant\Concerns\IsTenantModel;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

final class Company extends SpatieTenant implements TenantContract
{
    use IsTenantModel;
}
```

Point the engine at it in `config/vendra-tenant.php`:

```php
return [
    'model'       => App\Models\Company::class,
    'foreign_key' => 'company_id',
];
```

That is the whole wiring. Tenant-scoped models now stamp and scope themselves by
`company_id`, and `$document->tenant` resolves to a `Company`:

```php
use Misaf\VendraSupport\Tenancy\BelongsToTenant;

final class Document extends Model
{
    use BelongsToTenant;
}
```

```php
Schema::create('documents', function (Blueprint $table): void {
    $table->id();
    TenantSchema::addTenantColumn($table);   // emits `company_id`
    $table->string('title');
    TenantSchema::addTenantIndex($table);
});
```

Vendra's own wiring is the same two keys pointed at
`Misaf\VendraStore\Models\Store`. Vendra deliberately keeps `foreign_key` as the
neutral `tenant_id`, so every reusable domain package (`products`, `blog_posts`,
`roles`) works unchanged under any tenant model; only records describing the
Store itself carry `store_id`, and those belong to `misaf/vendra-store`.

The relation stays `tenant()` rather than gaining a business alias: an alias
would have to be registered into Eloquent's process-wide relation resolvers at
model-boot time, which makes a model's API depend on whichever configuration was
loaded when its class first booted. Domain code reads `$product->tenant`, and in
Vendra that *is* the Store.

## Resolving tenants from hosts

Host-to-tenant mapping is business knowledge — it needs a domains table this
package does not own — so the engine depends on a port and the application binds
the adapter:

```php
$this->app->bind(HostTenantFinder::class, CompanyDomainFinder::class);
```

Without a binding, `NullHostTenantFinder` resolves nothing. In Vendra,
`misaf/vendra-store` binds `StoreDomainFinder`.

`config/vendra-tenant.php` derives `central_host` from `APP_URL`. The registered
`GET /caddy/domain-check?domain=…` route authorizes only hosts the bound finder
recognises, and only accepts loopback requests; expose it to Caddy through the
local reverse proxy, not directly to the public internet.

## Enabling tenancy after domain migrations

If domain packages were migrated before this provider was installed, create the
tenant that should own the existing records and run:

```bash
php artisan vendra-tenant:enable {tenant-id-or-slug}
```

The command consumes the tenant table registry, adds the configured foreign key
and its index on the registered connections, assigns unscoped records to the
selected tenant, and can be rerun safely. Interactive confirmation is enabled by
default; use `--force` only for intentional non-interactive deployment.

Generate isolated route caches for every tenant with:

```bash
php artisan tenants:artisan route:cache
```

## Testing

Run the package checks from the package directory:

```bash
composer test
composer analyse
```

The suite drives the engine through a `Workspace` fixture rather than through
Vendra's Store — a model with a different name, table and foreign key — so a
business assumption leaking back into this package fails a test.

## License

MIT. See [LICENSE](LICENSE).
