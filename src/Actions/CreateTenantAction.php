<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Actions;

use Illuminate\Support\Facades\DB;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraUser\Actions\CreateUserAction;
use Misaf\VendraUser\Models\User;

final class CreateTenantAction
{
    public function __construct(private readonly CreateUserAction $createUserAction) {}

    /**
     * @return array{tenant: Tenant, user: User}
     */
    public function execute(
        string $name,
        string $domain,
        string $username,
        string $email,
        string $password,
    ): array {
        return DB::transaction(function () use (
            $name,
            $domain,
            $username,
            $email,
            $password,
        ): array {
            $createdTenant = Tenant::query()->create([
                'name'   => $name,
                'slug'   => $name,
                'status' => true,
            ]);

            $createdTenant->tenantDomains()->create([
                'name'   => $domain,
                'slug'   => $domain,
                'status' => true,
            ]);

            $createdUser = $this->createUserAction->execute(
                tenant: $createdTenant,
                username: $username,
                email: $email,
                password: $password,
                isVerified: true,
            );

            $createdUser->tenants()->syncWithoutDetaching([$createdTenant->getKey()]);

            return [
                'tenant' => $createdTenant,
                'user'   => $createdUser,
            ];
        });
    }
}
