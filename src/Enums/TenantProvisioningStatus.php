<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Enums;

enum TenantProvisioningStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
