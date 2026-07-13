<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Misaf\VendraTenant\Http\Controllers\AuthorizeCaddyDomainController;

Route::get('/caddy/domain-check', AuthorizeCaddyDomainController::class)
    ->name('vendra-tenant.caddy.domain-check');
