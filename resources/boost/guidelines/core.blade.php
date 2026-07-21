## Vendra Tenant

The `misaf/vendra-tenant` package is the concrete multi-tenancy **provider**. Installing it makes the application tenant-aware by binding a real `TenantResolver` over the support layer's default null resolver.

### Standards

### Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

- Keep tenant-provider code inside `packages/vendra-tenant` using the `Misaf\VendraTenant` namespace.
- This package owns the concrete tenant models (`Tenant`, `TenantDomain`), `Support\VendraTenantResolver` (binds `Misaf\VendraSupport\Contracts\TenantResolver`), `Services\DomainTenantFinder`, the switch tasks (`SwitchAppTask`, `SwitchMailTask`), `Jobs\CacheTenantRoutesJob`, `TenantPlugin`, and `TenantServiceProvider`. It is built on Spatie multitenancy.
- `Jobs\CacheTenantRoutesJob` regenerates one tenant's route cache off the request lifecycle. It implements Spatie's `NotTenantAware` (it targets its tenant via `--tenant` and may be dispatched with no current tenant, e.g. from a platform panel) — keep any queued job that runs across tenants `NotTenantAware`, or Spatie throws `CurrentTenantCouldNotBeDeterminedInTenantAwareJob`.
- The `tenants` table carries a nullable `account_id` (a billing account owned by `misaf/vendra-subscription`). It is a plain indexed column with no cross-package foreign key or relation on `Tenant` — the `Account → Tenant` relation lives on the `Account` side only.
- **This is the single module allowed to reference the concrete tenant.** All tenant switching, resolution, and Spatie wiring lives here.
- No other module may depend on `misaf/vendra-tenant`, with one documented exception: `misaf/vendra-subscription`, which owns tenant provisioning. All other domain and API modules consume tenancy only through `misaf/vendra-support` (`TenantResolver`, `TenantAwareness`, `BelongsToTenant`). Do not create further reverse dependencies.
- This applies to test suites too: module tests must not import `Misaf\VendraTenant` or declare a `misaf/vendra-tenant` dev dependency — they use the `misaf/vendra-testing` tenancy helpers (`makeCurrentTestTenant()`, `createTestTenant()`, `switchToTestTenant()`, …). Only this package's and `vendra-subscription`'s tests may import the concrete tenant; the root `tests/Feature/PackageManifestConsistencyTest.php` guard enforces it.
- Keep `VendraTenantResolver` a faithful implementation of the support `TenantResolver` contract; when the contract changes, update this resolver and the null resolver together.
- `vendra-tenant:enable {tenant}` is the explicit installation-order recovery path. It consumes the support `TenantTableRegistry`, adds missing `tenant_id` columns and indexes, backfills unscoped records to the selected tenant, and is safe to rerun.
- Keep per-tenant route caches because tenant route sets may diverge. Generate them with `php artisan tenants:artisan route:cache` and do not replace Spatie's `SwitchRouteCacheTask` with a project-specific subclass. Tests should remove only this task from `multitenancy.switch_tenant_tasks` instead of requiring generated cache files.
- Keep `searchOptions` scoped to enabled tenants (the `Tenant::enabled()` scope on `status`); tenant pickers and prompts must never offer disabled tenants.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets. This module legitimately references the tenant, so it does not assert a `not->toUse('Misaf\VendraTenant')` expectation.
