<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Providers;

use Composer\InstalledVersions;

use Filament\Panel;
use Illuminate\Foundation\Console\AboutCommand;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraSupport\Filament\Concerns\ResolvesConfiguredPanels;
use Misaf\VendraTenant\Support\VendraTenantResolver;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class TenantServiceProvider extends PackageServiceProvider
{
    use ResolvesConfiguredPanels;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('vendra-tenant')
            ->hasTranslations()
            ->hasMigrations([
                'create_tenants_table',
            ])
            ->hasRoute('web')
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-tenant');
            });
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(TenantResolver::class, VendraTenantResolver::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ( ! $this->shouldRegisterOnPanel($panel->getId(), 'vendra-tenant')) {
                return;
            }

            // $panel->plugin(TenantPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Vendra Tenant', fn() => ['Version' => InstalledVersions::getPrettyVersion('misaf/vendra-tenant')]);
    }
}
