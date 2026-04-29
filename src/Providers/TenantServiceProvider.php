<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Providers;

use Filament\Panel;
use Illuminate\Foundation\Console\AboutCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class TenantServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('vendra-tenant')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_tenants_table'
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->askToStarRepoOnGitHub('misaf/vendra-tenant');
            });
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ('admin' !== $panel->getId()) {
                return;
            }

            // $panel->plugin(TenantPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Vendra Tenant', fn() => ['Version' => 'dev-master']);
    }
}
