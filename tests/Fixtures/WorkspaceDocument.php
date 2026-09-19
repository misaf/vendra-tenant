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
 * A record owned through a configured `workspace_id` foreign key.
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
