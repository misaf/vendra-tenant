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
 * A reusable, tenant-aware model exactly as a domain package would write one:
 * it names no tenant, and its table carries the neutral `tenant_id`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 */
#[Unguarded]
#[Table(name: 'generic_documents')]
#[WithoutTimestamps]
final class Document extends Model
{
    use BelongsToTenant;
    use HasFactory;
}
