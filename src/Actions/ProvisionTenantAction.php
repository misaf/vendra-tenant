<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Misaf\VendraPermission\Actions\CreateRoleAction;
use Misaf\VendraPermission\Models\Role;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Models\User;

final class ProvisionTenantAction
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly CreateRoleAction $createRoleAction,
    ) {}

    /**
     * @param array{
     *     name: string,
     *     description: string|null,
     *     domain: string,
     *     username: string,
     *     email: string,
     *     password: string,
     *     role: string,
     *     guard: string
     * } $data
     * @return array{tenant: Tenant, user: User, role: Role}
     */
    public function execute(array $data, bool $isEnabled, bool $isVerified): array
    {
        $tenant = Tenant::query()->create([
            'name'   => $data['name'],
            'slug'   => $data['name'],
            'status' => $isEnabled,
        ]);

        $tenant->tenantDomains()->create([
            'name'   => $data['domain'],
            'slug'   => $data['domain'],
            'status' => $isEnabled,
        ]);

        $user = $this->createUserAction->execute(
            tenant: $tenant,
            username: $data['username'],
            email: $data['email'],
            password: $data['password'],
            isVerified: $isVerified,
        );

        $user->tenants()->syncWithoutDetaching([$tenant->id]);

        $role = $this->createRoleAction->execute(
            tenant: $tenant,
            name: $data['role'],
            guardName: $data['guard'],
        );

        $user->assignRole($role);

        return [
            'tenant' => $tenant,
            'user'   => $user,
            'role'   => $role,
        ];
    }
}
