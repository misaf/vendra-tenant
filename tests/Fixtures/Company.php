<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Misaf\VendraTenant\Concerns\IsTenantModel;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * A tenant keyed by `company_id` and slugged by `code`, owning rows through `tenant_id`.
 *
 * @property int $company_id
 * @property string $name
 * @property string $code
 */
#[Unguarded]
#[Table(name: 'companies', key: 'company_id')]
#[WithoutTimestamps]
final class Company extends SpatieTenant implements TenantContract
{
    use IsTenantModel;

    public function getTenantSlugName(): string
    {
        return 'code';
    }
}
