<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Providers;

use Illuminate\Support\ServiceProvider;

final class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/multitenancy.php', 'multitenancy');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'multitenancy');

        $this->publishes([
            __DIR__ . '/../../resources/lang' => $this->app->langPath('vendor/multitenancy'),
        ], 'multitenancy-lang');
    }
}
