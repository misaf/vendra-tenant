<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Tenancy\BelongsToTenant;

/**
 * A tenant-scoped record owned through `workspace_id`, so the suite proves the
 * scoping mechanism reads its foreign key from configuration instead of
 * assuming `tenant_id`.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $title
 */
final class WorkspaceDocument extends Model
{
    use BelongsToTenant;

    protected $table = 'workspace_documents';

    protected $guarded = [];

    public $timestamps = false;
}
