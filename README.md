# Vendra Tenant

The concrete multi-tenancy provider for Vendra applications, built on Spatie
Laravel Multitenancy.

Installing this package activates tenant awareness across domain packages by
binding the shared `TenantResolver` contract to `VendraTenantResolver`. Domain
packages remain coupled only to `misaf/vendra-support`.

## Features

- Tenant and tenant-domain models
- Domain-based tenant discovery
- Application and mail configuration switch tasks
- Per-tenant route caches
- Filament tenant management integration
- Recovery command for domain tables migrated before tenancy was enabled

## Requirements

- PHP 8.3+
- Laravel 13
- `misaf/vendra-support`
- `spatie/laravel-multitenancy`

## Installation

```bash
composer require misaf/vendra-tenant
php artisan vendor:publish --tag=vendra-tenant-migrations
php artisan migrate
```

Optionally publish the configuration and translations:

```bash
php artisan vendor:publish --tag=vendra-tenant-config
php artisan vendor:publish --tag=vendra-tenant-translations
```

Tenant provisioning belongs to the host application. Domain modules discover
the active tenant only through the resolver supplied by this package.

## Enabling Tenancy After Domain Migrations

If Vendra domain packages were migrated before this provider was installed,
create the tenant that should own the existing records and run:

```bash
php artisan vendra-tenant:enable {tenant-id-or-slug}
```

The command consumes the domain table registry, adds missing tenant columns and
indexes on their registered connections, assigns unscoped records to the
selected tenant, and can be rerun safely. Interactive confirmation is enabled
by default; use `--force` only for intentional non-interactive deployment.

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

## License

MIT. See [LICENSE](LICENSE).
