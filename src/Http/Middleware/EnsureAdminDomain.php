<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Misaf\VendraTenant\Services\DomainTenantFinder;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureAdminDomain
{
    public function __construct(private DomainTenantFinder $tenantFinder) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantFinder->findForAdminHost($request->getHost());

        abort_if(null === $tenant, Response::HTTP_NOT_FOUND);

        if ( ! $tenant->isCurrent()) {
            $tenant->makeCurrent();
        }

        return $next($request);
    }
}
