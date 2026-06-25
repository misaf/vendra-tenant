<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Misaf\VendraPermission\Actions\CreateRoleAction;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Models\User;
use RuntimeException;

final class ProvisionTenantAction
{
    /**
     * @var list<string>
     */
    private const array SEED_COMMANDS = [
        'vendra-permission:seed',
        'vendra-user:seed',
        'vendra-currency:seed',
        'vendra-product:seed',
        'vendra-faq:seed',
        'vendra-custom-page:seed',
        'vendra-tagger:seed',
        'vendra-language:seed',
    ];

    public function __construct(
        private readonly CreateTenantAction $createTenantAction,
        private readonly CreateRoleAction $createRoleAction,
    ) {}

    /**
     * @param array{
     *     name: string,
     *     domain: string,
     *     username: string,
     *     email: string
     * } $data
     * @return array{tenant: Tenant, user: User, password: string}
     */
    public function execute(array $data, bool $shouldSeed = false): array
    {
        $password = Str::password(length: 8, letters: true, numbers: true, symbols: false);

        $result = DB::transaction(function () use ($data, $password, $shouldSeed): array {
            $result = $this->createTenantAction->execute(
                name: $data['name'],
                domain: $data['domain'],
                username: $data['username'],
                email: $data['email'],
                password: $password,
            );

            $role = $this->createRoleAction->execute(
                tenant: $result['tenant'],
                name: Config::string('vendra-permission.super_admin_role'),
            );

            $result['tenant']->execute(fn() => $result['user']->assignRole($role));

            if ($shouldSeed) {
                $this->seedTenant($result['tenant']);
            }

            return [
                ...$result,
                'password' => $password,
            ];
        });

        $this->cacheTenantRoutes($result['tenant']);

        return $result;
    }

    private function seedTenant(Tenant $tenant): void
    {
        foreach (self::SEED_COMMANDS as $command) {
            $exitCode = Artisan::call($command, [
                'tenant'  => $tenant->slug,
                'seeders' => ['all'],
            ]);

            if (0 !== $exitCode) {
                throw new RuntimeException(sprintf(
                    'Seed command [%s] failed with exit code [%d].',
                    $command,
                    $exitCode,
                ));
            }
        }
    }

    private function cacheTenantRoutes(Tenant $tenant): void
    {
        $exitCode = Artisan::call('tenants:artisan', [
            'artisanCommand' => 'route:cache',
            '--tenant'       => [$tenant->getKey()],
        ]);

        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf(
                'Tenant route cache command failed with exit code [%d].',
                $exitCode,
            ));
        }
    }
}
