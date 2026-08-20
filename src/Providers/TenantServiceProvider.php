<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Providers;

use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Event;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Console\Commands\EnableTenancyCommand;
use Misaf\VendraTenant\Contracts\HostTenantFinder;
use Misaf\VendraTenant\Listeners\AddCurrentTenantToRequestJobContext;
use Misaf\VendraTenant\Listeners\RemoveCurrentTenantFromRequestJobContext;
use Misaf\VendraTenant\Support\ConfiguredTenantResolver;
use Misaf\VendraTenant\Support\NullHostTenantFinder;
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
            ->hasCommand(EnableTenancyCommand::class)
            ->hasRoute('web')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-tenant');
            });
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(TenantResolver::class, ConfiguredTenantResolver::class);

        /*
         | How a host maps to a tenant is business knowledge. The engine ships
         | the inert adapter as a default only — `bindIf`, so the package that
         | owns tenant domains (misaf/vendra-store here) wins whether it
         | registers before or after this provider.
         */
        $this->app->bindIf(HostTenantFinder::class, NullHostTenantFinder::class);
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Vendra Tenant', fn(): array => ['Version' => InstalledVersions::getPrettyVersion('misaf/vendra-tenant')]);

        Event::listen(MadeTenantCurrentEvent::class, AddCurrentTenantToRequestJobContext::class);
        Event::listen(ForgotCurrentTenantEvent::class, RemoveCurrentTenantFromRequestJobContext::class);
    }
}
