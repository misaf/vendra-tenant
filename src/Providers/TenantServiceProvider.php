<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Providers;

use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Event;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Console\Commands\EnableTenancyCommand;
use Misaf\VendraTenant\Listeners\AddCurrentTenantToRequestJobContext;
use Misaf\VendraTenant\Listeners\RemoveCurrentTenantFromRequestJobContext;
use Misaf\VendraTenant\Support\VendraTenantResolver;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;

final class TenantServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('vendra-tenant')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_tenants_table',
            ])
            ->hasCommand(EnableTenancyCommand::class)
            ->hasRoute('web')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-tenant');
            });
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(TenantResolver::class, VendraTenantResolver::class);
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Vendra Tenant', fn(): array => ['Version' => InstalledVersions::getPrettyVersion('misaf/vendra-tenant')]);

        Event::listen(MadeTenantCurrentEvent::class, AddCurrentTenantToRequestJobContext::class);
        Event::listen(ForgotCurrentTenantEvent::class, RemoveCurrentTenantFromRequestJobContext::class);
    }
}
