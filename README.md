# Vendra Tenant

Tenant management for Vendra applications.

## Enabling Tenancy After Domain Migrations

If Vendra domain packages were migrated before this provider was installed, create the tenant that should own the existing records and run:

```bash
php artisan vendra-tenant:enable {tenant-id-or-slug}
```

The command adds missing tenant columns and indexes, assigns unscoped records to that tenant, and can be rerun safely.

## Requirements

- PHP 8.2+
- Laravel 12
- Filament 5
- Livewire 4
- Pest 4
- Tailwind CSS 4

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
