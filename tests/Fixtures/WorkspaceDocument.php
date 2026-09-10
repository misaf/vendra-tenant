<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[Unguarded]
#[Table(name: 'workspace_documents')]
#[WithoutTimestamps]
final class WorkspaceDocument extends Model
{
    use BelongsToTenant;
    use HasFactory;
}
