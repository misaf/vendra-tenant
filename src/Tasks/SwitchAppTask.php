<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Misaf\VendraTenant\Contracts\TenantContract;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchAppTask implements SwitchTenantTask
{
    /**
     * The locale and timezone a tenant that states no preference of its own
     * runs under. Vendra's fleet is Iranian, so these are the fleet's defaults
     * rather than the framework's — a tenant that sets `locale`/`timezone`
     * overrides them.
     */
    private const string FALLBACK_LOCALE = 'fa';

    private const string FALLBACK_TIMEZONE = 'Asia/Tehran';

    private string $originalLocale;

    private string $originalName;

    private string $originalProgressBarColor;

    private string $originalTimezone;

    private string $originalUrl;

    private string $originalAssetUrl;

    public function __construct()
    {
        $this->originalLocale = Config::string('app.locale');
        $this->originalName = Config::string('app.name');
        $this->originalProgressBarColor = Config::string('livewire.navigate.progress_bar_color');
        $this->originalTimezone = Config::string('app.timezone');
        $this->originalUrl = Config::string('app.url');
        $this->originalAssetUrl = Config::string('app.asset_url');
    }

    public function forgetCurrent(): void
    {
        Config::set('app.locale', $this->originalLocale);
        Config::set('app.name', $this->originalName);
        Config::set('livewire.navigate.progress_bar_color', $this->originalProgressBarColor);
        Config::set('app.timezone', $this->originalTimezone);
        Config::set('app.url', $this->originalUrl);
        Config::set('app.asset_url', $this->originalAssetUrl);

        URL::forceRootUrl($this->originalUrl);
        URL::useAssetOrigin($this->originalAssetUrl);
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        $appUrl = request()->schemeAndHttpHost();

        Config::set('app.locale', $this->tenantLocale($tenant));
        Config::set('app.name', $tenant instanceof TenantContract ? $tenant->getTenantName() : $this->originalName);
        Config::set('livewire.navigate.progress_bar_color', '#f59e0b');
        Config::set('app.timezone', $this->tenantTimezone($tenant));
        Config::set('app.url', $appUrl);
        Config::set('app.asset_url', $appUrl);

        URL::forceRootUrl($appUrl);
        URL::useAssetOrigin($appUrl);
    }

    private function tenantLocale(IsTenant $tenant): string
    {
        $locale = $tenant instanceof TenantContract ? $tenant->getTenantLocale() : null;

        return $locale ?? self::FALLBACK_LOCALE;
    }

    private function tenantTimezone(IsTenant $tenant): string
    {
        $timezone = $tenant instanceof TenantContract ? $tenant->getTenantTimezone() : null;

        return $timezone ?? self::FALLBACK_TIMEZONE;
    }
}
