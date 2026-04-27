<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchAppTask implements SwitchTenantTask
{
    private string $originalLocale;

    private string $originalName;

    private string $originalTimezone;

    private string $originalUrlScheme;

    public function __construct()
    {
        $this->originalLocale = Config::string('app.locale');
        $this->originalName = Config::string('app.name');
        $this->originalTimezone = Config::string('app.timezone');
        $this->originalUrlScheme = parse_url(Config::string('app.url'), PHP_URL_SCHEME) ?: 'https';
    }

    public function forgetCurrent(): void
    {
        Config::set('app.locale', $this->originalLocale);
        Config::set('app.name', $this->originalName);
        Config::set('app.timezone', $this->originalTimezone);

        $this->setAppUrl($this->originalUrlScheme, parse_url(Config::string('app.url'), PHP_URL_HOST) ?? '');
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        Config::set('app.locale', 'en');
        Config::set('app.name', $tenant->name);
        Config::set('app.timezone', 'Asia/Tehran');

        $this->setAppUrl(
            $this->originalUrlScheme,
            request()->getHost(),
        );

        Config::set('livewire.navigate.progress_bar_color', ['progress_color' => 'rgb(245, 158, 11)']);
    }

    private function setAppUrl(string $scheme, string $host): void
    {
        $url = "{$scheme}://{$host}";
        Config::set('app.url', $url);
        URL::forceRootUrl($url);
    }
}
