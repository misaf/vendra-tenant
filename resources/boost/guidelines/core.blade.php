## Vendra Tenant

The `misaf/vendra-tenant` package is the concrete multi-tenancy **provider**. Installing it makes the application tenant-aware by binding a real `TenantResolver` over the support layer's default null resolver.

### Standards

- Keep tenant-provider code inside `packages/vendra-tenant` using the `Misaf\VendraTenant` namespace.
- This package owns the concrete tenant models (`Tenant`, `TenantDomain`), `Support\VendraTenantResolver` (binds `Misaf\VendraSupport\Contracts\TenantResolver`), `Services\DomainTenantFinder`, the switch tasks (`SwitchAppTask`, `SwitchMailTask`), `TenantPlugin`, and `TenantServiceProvider`. It is built on Spatie multitenancy.
- **This is the single module allowed to reference the concrete tenant.** All tenant switching, resolution, and Spatie wiring lives here.
- No other module may depend on `misaf/vendra-tenant`, with one documented exception: `misaf/vendra-subscription`, which owns tenant provisioning. All other domain and API modules consume tenancy only through `misaf/vendra-support` (`TenantResolver`, `TenantAwareness`, `BelongsToTenant`). Do not create further reverse dependencies.
- Keep `VendraTenantResolver` a faithful implementation of the support `TenantResolver` contract; when the contract changes, update this resolver and the null resolver together.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets. This module legitimately references the tenant, so it does not assert a `not->toUse('Misaf\VendraTenant')` expectation.
