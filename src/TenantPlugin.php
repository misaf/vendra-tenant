<?php

declare(strict_types=1);

namespace Misaf\VendraTenant;

use Filament\Contracts\Plugin;
use Filament\Panel;

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
        );
    }

    public function boot(Panel $panel): void {}
}
