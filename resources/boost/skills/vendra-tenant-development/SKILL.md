---
name: vendra-tenant-development
description: "Use this skill when creating, modifying, reviewing, or testing the Vendra Tenant provider module in packages/vendra-tenant. Trigger for the Tenant / TenantDomain models, VendraTenantResolver, DomainTenantFinder, SwitchAppTask / SwitchMailTask, TenantPlugin, TenantServiceProvider, Spatie multitenancy wiring, and the TenantResolver binding that enables tenant awareness."
---

# Vendra Tenant

## Workflow

## Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

Always use this skill together with `laravel-best-practices` for Laravel PHP and `pest-testing` when tests are added or changed. Pair it with `vendra-support-development` whenever the `TenantResolver` contract is involved. Before code changes, use Laravel Boost `application-info` and `search-docs`.

## Module Boundary

Treat `packages/vendra-tenant` as the concrete multi-tenancy provider.

- Use namespace `Misaf\VendraTenant`.
- Own the concrete `Tenant` and `TenantDomain` models, `VendraTenantResolver`, `DomainTenantFinder`, the switch tasks, `TenantPlugin`, and `TenantServiceProvider` here.
- This is the only module permitted to reference the concrete tenant model and Spatie multitenancy APIs.
- No domain, API, or support module may depend on this package. Enabling tenancy is done by installing this provider, which binds `Misaf\VendraSupport\Contracts\TenantResolver` to `VendraTenantResolver`.

## Provider Responsibilities

- Bind `VendraTenantResolver` as the `TenantResolver` in `TenantServiceProvider`; it must implement every contract method (`available`, `current`, `currentId`, `modelClass`, `findByKeyOrSlug`, `makeCurrent`, `searchOptions`).
- Keep `searchOptions` scoped to enabled tenants (the `Tenant::enabled()` scope on `status`); tenant pickers and prompts must never offer disabled tenants.
- Keep tenant context switching (Spatie tasks such as `SwitchAppTask` / `SwitchMailTask`) inside this module.
- Keep Spatie's `SwitchRouteCacheTask` with separate cache files per tenant and generate them with `php artisan tenants:artisan route:cache`; do not add a custom route-cache switching task. In tests, remove only this task from the configured switch tasks so factory-created tenants do not require cache files.
- Keep domain resolution (`DomainTenantFinder`) and any tenant Filament wiring (`TenantPlugin`) here.

## Testing And Verification

- Keep tests purposeful: cover resolver contract conformance, domain resolution, and tenant switching.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets. Do not add a `not->toUse('Misaf\VendraTenant')` expectation — this module intentionally references the concrete tenant.
- Run module checks: `composer --working-dir=packages/vendra-tenant test` and `composer --working-dir=packages/vendra-tenant analyse`.
- If PHP files changed, run `vendor/bin/pint --dirty --format agent`.
