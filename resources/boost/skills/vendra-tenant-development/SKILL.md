---
name: vendra-tenant-development
description: "Create, modify, review, or test the Vendra Tenant provider module in packages/vendra-tenant. Use for the Tenant / TenantDomain models, provisioning accessibility and billing suspension, VendraTenantResolver, DomainTenantFinder, ReplaceTenantDomainAction, Caddy on-demand TLS domain authorization, SwitchAppTask / SwitchMailTask, TenantServiceProvider, EnableTenancyAction, the vendra-tenant:enable command, TenantTableRegistry schema retrofits, Spatie multitenancy wiring, and the TenantResolver binding that enables tenant awareness."
---

# Vendra Tenant

## Workflow

- Inspect `composer.json`, sibling files, and existing tests before changing the package.
- Use Laravel Boost `application-info` and `search-docs` before code changes.
- Apply `laravel-best-practices` to Laravel PHP and `pest-testing` whenever tests change.
- Apply `tailwindcss-development` only when changing Blade markup or Tailwind classes.
- Keep changes inside this package's boundary and preserve its public contracts.
- Add or update focused Pest coverage, then run `composer --working-dir=packages/vendra-tenant test` and `composer --working-dir=packages/vendra-tenant analyse`.

## Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

## Vendra Transitive API Policy

- Treat a Vendra dependency intentionally exposed through the public API of a directly required Vendra platform package as part of the supported public contract of that package.
- Do not add a redundant direct Composer requirement solely because source code imports a type from that exposed dependency.
- Apply this only to Vendra platform packages listed under `require`; never extend it to `require-dev`, `suggest`, incidental implementation dependencies, or third-party packages. Removing or replacing an exposed dependency is a breaking change; keep `self.version` alignment across the Vendra package graph.

## Module Boundary

Treat `packages/vendra-tenant` as the concrete multi-tenancy provider.

- Use namespace `Misaf\VendraTenant`.
- Own the concrete `Tenant` and `TenantDomain` models, `VendraTenantResolver`, `DomainTenantFinder`, `ReplaceTenantDomainAction`, `AuthorizeCaddyDomainController`, the switch tasks, `Jobs\CacheTenantRoutesJob` (implements Spatie `NotTenantAware`), `EnableTenancyAction`, `EnableTenancyCommand`, and `TenantServiceProvider` here. The `tenants` table carries a nullable `reseller_id` (a billing reseller owned by the host app, `App\Models\Reseller`) as a plain indexed column with no FK or `Tenant` relation; the inverse relation lives on the host model.
- Make queued notifications and jobs dispatched from host-level console or reseller flows implement Spatie `NotTenantAware`. These flows have no current tenant, so the default tenant-aware queue listener may delete their jobs before handling even while Horizon reports them as completed.
- This is the only module permitted to reference the concrete tenant model and Spatie multitenancy APIs.
- No domain, API, or support module may depend on this package. Enabling tenancy is done by installing this provider, which binds `Misaf\VendraSupport\Contracts\TenantResolver` to `VendraTenantResolver`.
- Module test suites must not import `Misaf\VendraTenant` either — they use the `misaf/vendra-testing` tenancy helpers (`makeCurrentTestTenant()`, `createTestTenant()`, `switchToTestTenant()`, …). Only this package's own tests may import the concrete tenant; the root `PackageManifestConsistencyTest` guard enforces it.

## Provider Responsibilities

- Bind `VendraTenantResolver` as the `TenantResolver` in `TenantServiceProvider`; it must implement every contract method (`available`, `current`, `currentId`, `modelClass`, `findByKeyOrSlug`, `makeCurrent`, `searchOptions`).
- Keep `vendra-tenant:enable {tenant}` as the explicit installation-order recovery path. Consume `TenantTableRegistry` from Support; never hard-code domain package tables inside Vendra Tenant.
- Require an existing tenant ID or slug before mutating schemas. Add missing `tenant_id` columns as nullable, backfill only unscoped rows to the selected tenant, add the tenant index, enforce non-nullability, clear `TenantSchema` caches, and keep reruns idempotent.
- Preserve registered database connections so tables such as Activity Log are retrofitted on the same connection used by their migrations. Keep interactive confirmation by default and reserve `--force` for intentional non-interactive execution.
- Keep `searchOptions` and `DomainTenantFinder` scoped through `Tenant::accessible()`: tenants must be active, provisioning-ready, and not billing-suspended before pickers or host resolution may expose them.
- Replace active domains through `ReplaceTenantDomainAction`; normalize and validate the replacement, retain the old domain as soft-deleted history, and create the new active domain inside the target tenant context.
- Keep the named `GET /caddy/domain-check` route loopback-only and validate its hostname before resolving it through `DomainTenantFinder`. It authorizes Caddy certificates only for accessible tenant domains and must not become a public tenant lookup endpoint.
- Keep tenant context switching (Spatie tasks such as `SwitchAppTask` / `SwitchMailTask`) inside this module.
- Keep Spatie's `SwitchRouteCacheTask` with separate cache files per tenant and generate them with `php artisan tenants:artisan route:cache`; do not add a custom route-cache switching task. In tests, remove only this task from the configured switch tasks so factory-created tenants do not require cache files.
- Keep domain resolution (`DomainTenantFinder`) in this module.

## Testing And Verification

- Keep tests purposeful: cover resolver contract conformance, domain resolution, tenant switching, missing-column retrofits, legacy-row backfills, index and nullability restoration, schema-cache refresh, invalid-tenant safety, and idempotency.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets. Do not add a `not->toUse('Misaf\VendraTenant')` expectation — this module intentionally references the concrete tenant.
- Run module checks: `composer --working-dir=packages/vendra-tenant test` and `composer --working-dir=packages/vendra-tenant analyse`.
- If PHP files changed, run `vendor/bin/pint --dirty --format agent`.
