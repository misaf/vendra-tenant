<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Misaf\VendraTenant\Concerns\IsTenantModel;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * A second, unrelated ecosystem's tenant.
 *
 * `Company` holds two rules at once. The concrete tenant model's *name* and the
 * generic ownership *column* are independent: companies own `generic_documents`
 * through the neutral `tenant_id`, exactly as Vendra's Store owns `products`.
 * And the tenant's *own* columns are its business, not the engine's — this one
 * is keyed by `company_id` and slugged by `code`, neither of which the resolver
 * is allowed to assume.
 *
 * @property int $company_id
 * @property string $name
 * @property string $code
 */
final class Company extends SpatieTenant implements TenantContract
{
    use IsTenantModel;

    protected $table = 'companies';

    protected $primaryKey = 'company_id';

    protected $guarded = [];

    public $timestamps = false;

    public function getTenantSlugName(): string
    {
        return 'code';
    }
}
