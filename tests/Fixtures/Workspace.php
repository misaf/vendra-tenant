<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Misaf\VendraTenant\Concerns\IsTenantModel;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * A tenant model this engine has never heard of.
 *
 * The suite drives the engine through a `Workspace` owning `workspace_id`
 * columns rather than through Vendra's own Store, which is the point: if the
 * tests pass against a model with a different name, a different table and a
 * different foreign key, nothing ecommerce-specific has leaked back into
 * `misaf/vendra-tenant`.
 *
 * Its columns are deliberately hostile to the engine's old assumptions: the
 * primary key is `uuid` and the slug is `handle`. (The key still *holds* an
 * integer — `TenantContract::getTenantKey()` returns `int` and the generic
 * tenant foreign key is an integer column, both of which are settled
 * architecture. Only the column *name* varies here, which is what the resolver
 * had been hard-coding.)
 *
 * @property int $uuid
 * @property string $name
 * @property string $handle
 * @property bool $active
 */
final class Workspace extends SpatieTenant implements TenantContract
{
    use IsTenantModel;

    protected $table = 'workspaces';

    protected $primaryKey = 'uuid';

    protected $guarded = [];

    public $timestamps = false;

    public function getTenantSlugName(): string
    {
        return 'handle';
    }

    /**
     * @param  Builder<Workspace>  $query
     * @return Builder<Workspace>
     */
    public function scopeAccessible(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uuid'   => 'integer',
            'active' => 'boolean',
        ];
    }
}
