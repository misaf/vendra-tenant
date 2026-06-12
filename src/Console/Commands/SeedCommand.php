<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Console\Commands;

use Misaf\VendraSupport\Console\Commands\BaseSeedCommand;
use Misaf\VendraTenant\Database\Seeders\DemoContentSeeder;
use Misaf\VendraTenant\TenantPlugin;

final class SeedCommand extends BaseSeedCommand
{
    protected const string MODULE_NAME = TenantPlugin::ID;

    protected $signature = self::MODULE_NAME . ':seed
        {tenant : Tenant ID or slug to seed blog data for}
        {seeders* : Seeder keys to run. Use "all" or one or more of: demo-contents}';

    protected $description = 'Seed blog module data for a tenant';

    /**
     * @return array<string, class-string>
     */
    protected function seeders(): array
    {
        return [
            'demo-contents' => DemoContentSeeder::class,
        ];
    }
}
