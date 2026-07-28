<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Listeners;

use Misaf\VendraSupport\Context\RequestJobContext;
use Misaf\VendraTenant\Models\Tenant;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;

final class AddCurrentTenantToRequestJobContext
{
    public function handle(MadeTenantCurrentEvent $event): void
    {
        if ( ! $event->tenant instanceof Tenant) {
            return;
        }

        (new RequestJobContext(tenantId: $event->tenant->getKey()))->add();
    }
}
