---
name: vendra-tenant-development
description: "Create, modify, review, or test the generic Vendra Tenant engine in packages/vendra-tenant. Use for TenantContract, IsTenantModel, HostTenantFinder, NullHostTenantFinder, ConfiguredTenantResolver, the configurable tenant model and foreign key, TenantProvisioningStatus, Caddy on-demand TLS domain authorization, EnsureAdminDomain, SwitchAppTask / SwitchMailTask, CacheTenantRoutesJob, TenantServiceProvider, EnableTenancyAction, the vendra-tenant:enable command, TenantTableRegistry schema retrofits, Spatie multitenancy wiring, and the TenantResolver binding that enables tenant awareness."
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

Treat `packages/vendra-tenant` as the **generic multi-tenancy engine**. Tenant is a technical role, not a business entity: this module owns the mechanism and never the model that plays it.

- Use namespace `Misaf\VendraTenant`.
- Own `Contracts\TenantContract`, `Contracts\HostTenantFinder`, `Concerns\IsTenantModel`, `Support\ConfiguredTenantResolver`, `Support\NullHostTenantFinder`, `Enums\TenantProvisioningStatus`, `Http\Middleware\EnsureAdminDomain`, `Http\Controllers\AuthorizeCaddyDomainController`, the switch tasks, `Jobs\CacheTenantRoutesJob` (implements Spatie `NotTenantAware`), `EnableTenancyAction`, `EnableTenancyCommand`, and `TenantServiceProvider`.
- **Own no concrete tenant model, and name no business one.** Never import `Misaf\VendraStore`, `Misaf\VendraReseller`, `Misaf\VendraConsole`, or `Misaf\VendraSubscription` — in source, config defaults, or tests. In Vendra the configured model is `Misaf\VendraStore\Models\Store`, and `Store::accessible()`, the store domains and `ReplaceStoreDomainAction` all live in `misaf/vendra-store`.
- Read every business-shaped value from configuration: `vendra-tenant.model` and `vendra-tenant.foreign_key`. Go through `TenantSchema::column()` or the bound resolver; never hard-code `tenant_id`. Do not add a business-named relation alias: registering one into Eloquent's process-wide relation resolvers at model-boot time makes a model's API depend on whichever config was loaded when its class first booted, which breaks across tests and requests. The relation stays `tenant()`.
- Make queued notifications and jobs dispatched from host-level console or reseller flows implement Spatie `NotTenantAware`. These flows have no current tenant, so the default tenant-aware queue listener may delete their jobs before handling even while Horizon reports them as completed.
- Only the package supplying the concrete tenant model (`misaf/vendra-store`) and the panels above it may depend on this package. Domain and API modules enable tenancy simply by having this provider installed, which binds `Misaf\VendraSupport\Contracts\TenantResolver` to `ConfiguredTenantResolver`.
- Module test suites must not import `Misaf\VendraTenant` either — they use the `misaf/vendra-testing` tenancy helpers (`makeCurrentTestTenant()`, `createTestTenant()`, `switchToTestTenant()`, …). The root `PackageManifestConsistencyTest` guard enforces it.

## Provider Responsibilities

- Bind `ConfiguredTenantResolver` as the `TenantResolver` in `TenantServiceProvider`; it must implement every contract method (`available`, `current`, `currentId`, `modelClass`, `foreignKey`, `findByKeyOrSlug`, `makeCurrent`, `execute`, `eachTenant`, `searchOptions`). Changing the contract means updating `NullTenantResolver` and the `mock(TenantResolver::class)` setups in other packages' tests in the same change.
- Bind ports with `bindIf` and adapters with `bind`: `HostTenantFinder` defaults to `NullHostTenantFinder` here, and `misaf/vendra-store` binds `StoreDomainFinder` over it. Provider discovery is alphabetical, so a plain `bind` on the default would win.
- Validate `vendra-tenant.model` where it is used: it must be an Eloquent model implementing `TenantContract`, or the resolver throws a pointed configuration error.
- Treat business availability as the concrete model's business: apply an `accessible` scope when the configured model defines one, and list every tenant when it does not.
- Keep `vendra-tenant:enable {tenant}` as the explicit installation-order recovery path. Consume `TenantTableRegistry` from Support; never hard-code domain package tables inside Vendra Tenant.
- Require an existing tenant ID or slug before mutating schemas. Add the configured foreign key as nullable, backfill only unscoped rows to the selected tenant, add its index, enforce non-nullability, clear `TenantSchema` caches, and keep reruns idempotent.
- Preserve registered database connections so tables such as Activity Log are retrofitted on the same connection used by their migrations. Keep interactive confirmation by default and reserve `--force` for intentional non-interactive execution.
- Keep the named `GET /caddy/domain-check` route loopback-only and validate its hostname before resolving it through the bound `HostTenantFinder`. It authorizes Caddy certificates only for hosts that finder recognises and must not become a public tenant lookup endpoint.
- Keep tenant context switching (Spatie tasks such as `SwitchAppTask` / `SwitchMailTask`) inside this module, and read tenant identity through `TenantContract` (`getTenantKey`, `getTenantName`, `getTenantSlug`) rather than concrete columns.
- Keep Spatie's `SwitchRouteCacheTask` with separate cache files per tenant and generate them with `php artisan tenants:artisan route:cache`; do not add a custom route-cache switching task. In tests, remove only this task from the configured switch tasks so factory-created tenants do not require cache files.

## Testing And Verification

- Drive the suite through `tests/Fixtures/Workspace` — a tenant model with a different name, table and `workspace_id` foreign key — so a business assumption leaking into this package fails a test instead of passing quietly.
- Keep tests purposeful: cover resolver contract conformance, configurable model and foreign key, context establish/restore, cross-tenant isolation, the business relation alias, missing-column retrofits, legacy-row backfills, index and nullability restoration, schema-cache refresh, invalid-tenant safety, and idempotency.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets, plus the rules asserting this engine does not use `Misaf\VendraStore`, `Misaf\VendraReseller`, `Misaf\VendraConsole`, or `Misaf\VendraSubscription`.
- Run module checks: `composer --working-dir=packages/vendra-tenant test` and `composer --working-dir=packages/vendra-tenant analyse`.
- If PHP files changed, run `vendor/bin/pint --dirty --format agent`.
