<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Contracts\TenantResolver;
use Misaf\VendraTenant\Models\Tenant;
use RuntimeException;

final class VendraTenantResolver implements TenantResolver
{
    public function available(): bool
    {
        return true;
    }

    public function current(): ?Model
    {
        return Tenant::current();
    }

    public function currentId(): ?int
    {
        $tenant = $this->current();

        return $tenant instanceof Tenant ? (int) $tenant->getKey() : null;
    }

    public function modelClass(): string
    {
        return Tenant::class;
    }

    public function findByKeyOrSlug(int|string $tenant): ?Model
    {
        return Tenant::query()
            ->whereKey($tenant)
            ->orWhere('slug', $tenant)
            ->first();
    }

    public function makeCurrent(Model|int|string $tenant): bool
    {
        if ( ! $tenant instanceof Tenant) {
            $tenant = $this->findByKeyOrSlug($tenant);
        }

        if ( ! $tenant instanceof Tenant) {
            return false;
        }

        $tenant->makeCurrent();

        return true;
    }

    public function execute(Model|int|string $tenant, Closure $callback): mixed
    {
        if (is_int($tenant) || is_string($tenant)) {
            $tenant = $this->findByKeyOrSlug($tenant);
        }

        if ( ! $tenant instanceof Tenant) {
            throw new RuntimeException('The given tenant could not be resolved.');
        }

        return $tenant->execute($callback);
    }

    public function eachTenant(Closure $callback): void
    {
        Tenant::query()
            ->cursor()
            ->each(fn(Tenant $tenant): mixed => $tenant->execute($callback));
    }

    /**
     * @return array<int, string>
     */
    public function searchOptions(string $value, int $limit = 10): array
    {
        $search = mb_trim($value);

        $tenants = Tenant::query()
            ->select(['id', 'slug'])
            ->enabled()
            ->when('' !== $search, fn(Builder $query): Builder => $query->where('slug', 'like', "%{$search}%"))
            ->limit($limit)
            ->get();

        $options = [];

        foreach ($tenants as $tenant) {
            $options[(int) $tenant->id] = (string) $tenant->slug;
        }

        return $options;
    }
}
