<?php

declare(strict_types=1);

namespace Misaf\VendraTenant\Tasks;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

final class SwitchFacadesTask implements SwitchTenantTask
{
    public function forgetCurrent(): void
    {
        $this->clearFacadeInstancesInTheAppNamespace();
        $this->clearFacadeInstancesInTheAppModulesNamespace();
    }

    public function makeCurrent(IsTenant $tenant): void
    {
        // No actions required for makeCurrent in this task
    }

    private function clearFacadeInstancesInTheAppNamespace(): void
    {
        $this->clearFacadeInstancesByNamespace('App');
    }

    private function clearFacadeInstancesInTheAppModulesNamespace(): void
    {
        $this->clearFacadeInstancesByNamespace('Misaf');
    }

    private function clearFacadeInstancesByNamespace(string $namespace): void
    {
        // Collect all declared classes once and filter those that are facades within the specified namespace
        collect(get_declared_classes())
            ->filter(fn(string $className) => $this->isNamespaceFacade($className, $namespace))
            ->each(fn(string $className) => $this->clearResolvedFacadeInstance($className));
    }

    private function isNamespaceFacade(string $className, string $namespace): bool
    {
        return is_subclass_of($className, Facade::class)
               && (Str::startsWith($className, $namespace) || Str::startsWith($className, "Facades\\{$namespace}"));
    }

    private function clearResolvedFacadeInstance(string $className): void
    {
        $className::clearResolvedInstance($className::getFacadeAccessor());
    }
}
