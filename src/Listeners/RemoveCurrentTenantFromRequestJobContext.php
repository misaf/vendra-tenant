<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Listeners;

use Misaf\VendraSupport\Context\RequestJobContext;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;

final class RemoveCurrentTenantFromRequestJobContext
{
    public function handle(ForgotCurrentTenantEvent $event): void
    {
        RequestJobContext::forget(RequestJobContext::TENANT_ID);
    }
}
