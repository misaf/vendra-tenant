<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Misaf\VendraTenant\Concerns\IsTenantModel;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * A tenant owning `workspace_id` rows, keyed by `uuid` and slugged by `handle`.
 *
 * Proves nothing store-specific leaked into `misaf/vendra-tenant`.
 *
 * @property int $uuid
 * @property string $name
 * @property string $handle
 * @property bool $active
 */
#[Unguarded]
#[Table(name: 'workspaces', key: 'uuid')]
#[WithoutTimestamps]
final class Workspace extends SpatieTenant implements TenantContract
{
    use IsTenantModel;

    public function getTenantSlugName(): string
    {
        return 'handle';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function accessible(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uuid' => 'integer',
            'active' => 'boolean',
        ];
    }
}
