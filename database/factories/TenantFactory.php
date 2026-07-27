<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Misaf\VendraTenant\Enums\TenantProvisioningStatus;
use Misaf\VendraTenant\Models\Tenant;

/**
 * @extends Factory<Tenant>
 */
#[UseModel(Tenant::class)]
final class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                     => fake()->unique()->sentence(3),
            'description'              => fake()->text(),
            'slug'                     => fn(array $attributes) => Str::slug($attributes['name']),
            'active'                   => fake()->boolean(),
            'billing_suspended_at'     => null,
            'provisioning_status'      => TenantProvisioningStatus::Ready,
            'provisioning_should_seed' => false,
            'provisioned_at'           => now(),
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn(): array => ['active' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn(): array => ['active' => false]);
    }
}
