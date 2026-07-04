<?php

declare(strict_types=1);

namespace Misaf\VendraTenant;

use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Misaf\VendraTenant\Models\Tenant;

final class TenantPlugin implements Plugin
{
    public const string ID = 'vendra-tenant';

    public function getId(): string
    {
        return self::ID;
    }

    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(static::class);

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__ . '/Filament/Resources',
            for: 'Misaf\\VendraTenant\\Filament\\Resources',
        )
        // ->brandLogo(function () {
        //     $tenant = Tenant::current();

        //     return app()->environment('production') && $tenant
        //         ? asset('images/' . $tenant->slug . '.webp')
        //         : null;
        // })
            ->brandLogoHeight('10rem')
            ->defaultThemeMode(ThemeMode::Dark)
            ->font('yekan', 'https://cdn.font-store.ir/yekan.css', LocalFontProvider::class)
            ->maxContentWidth(Width::Full)
            ->tenant(Tenant::class)
            // ->brandName(fn(GeneralSettings $generalSettings) => $generalSettings?->site_title ?? 'Default')
            ->colors([
                'primary' => Color::Gray
            ]);
    }

    public function boot(Panel $panel): void {}
}
