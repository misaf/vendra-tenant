<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Misaf\VendraTenant\Models\Tenant;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchAppTask implements SwitchTenantTask
{
    private string $originalLocale;

    private string $originalName;

    private string $originalProgressBarColor;

    private string $originalTimezone;

    private string $originalUrl;

    public function __construct()
    {
        $this->originalLocale = Config::string('app.locale');
        $this->originalName = Config::string('app.name');
        $this->originalProgressBarColor = Config::string('livewire.navigate.progress_bar_color');
        $this->originalTimezone = Config::string('app.timezone');
        $this->originalUrl = Config::string('app.url');
    }

    public function forgetCurrent(): void
    {
        Config::set('app.locale', $this->originalLocale);
        Config::set('app.name', $this->originalName);
        Config::set('livewire.navigate.progress_bar_color', $this->originalProgressBarColor);
        Config::set('app.timezone', $this->originalTimezone);
        Config::set('app.url', $this->originalUrl);

        URL::forceRootUrl($this->originalUrl);
    }

    /**
     * @param Tenant $tenant
     */
    public function makeCurrent(IsTenant $tenant): void
    {
        $appUrl = request()->schemeAndHttpHost();

        Config::set('app.locale', 'en');
        Config::set('app.name', $tenant->name);
        Config::set('livewire.navigate.progress_bar_color', '#f59e0b');
        Config::set('app.timezone', 'Asia/Tehran');
        Config::set('app.url', $appUrl);

        URL::forceRootUrl($appUrl);
    }
}
