<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Support\Facades\DB;
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
     *     slug: string,
     *     domain: string,
     *     domain_description: string|null,
     *     domain_slug: string,
     *     username: string,
     *     email: string,
     *     password: string,
     *     role: string,
     *     role_description: string|null,
     *     guard: string
     * } $data
     * @return array{tenant: Tenant, user: User, role: Role}
     */
    public function execute(array $data, bool $isEnabled, bool $isVerified): array
    {
            $tenant = Tenant::query()->create([
                'name'        => $data['name'],
                'description' => $data['description'],
                'slug'        => $data['slug'],
                'status'      => $isEnabled,
            ]);

            $tenant->tenantDomains()->create([
                'name'        => $data['domain'],
                'description' => $data['domain_description'],
                'slug'        => $data['domain_slug'],
                'status'      => $isEnabled,
            ]);

            $user = $this->createUserAction->execute(
                tenant: $tenant,
                username: $data['username'],
                email: $data['email'],
                password: $data['password'],
                isVerified: $isVerified,
            );

            $role = $this->createRoleAction->execute(
                tenant: $tenant,
                name: $data['role'],
                description: $data['role_description'],
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
