<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Misaf\VendraTenant\Services\DomainTenantFinder;

final readonly class AuthorizeCaddyDomainController
{
    public function __construct(private DomainTenantFinder $tenantFinder) {}

    public function __invoke(Request $request): Response
    {
        abort_unless(
            in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true),
            Response::HTTP_NOT_FOUND,
        );

        $domain = $request->query('domain');

        abort_unless(
            is_string($domain)
                && filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
                && mb_strlen($domain) <= 253,
            Response::HTTP_NOT_FOUND,
        );

        abort_if(
            null === $this->tenantFinder->findForHost(mb_strtolower($domain)),
            Response::HTTP_NOT_FOUND,
        );

        return response('', Response::HTTP_OK);
    }
}
