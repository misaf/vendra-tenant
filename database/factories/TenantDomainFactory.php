<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Models\TenantDomain;

/**
 * @extends Factory<TenantDomain>
 */
#[UseModel(TenantDomain::class)]
final class TenantDomainFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'name'        => fake()->unique()->sentence(3),
            'description' => fake()->text(),
            'slug'        => fn(array $attributes) => Str::slug($attributes['name']),
            'status'      => fake()->boolean(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn(): array => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
