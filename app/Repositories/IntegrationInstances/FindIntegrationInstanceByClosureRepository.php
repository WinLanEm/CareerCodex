<?php

namespace App\Repositories\IntegrationInstances;

use App\Contracts\Repositories\IntegrationInstance\FindIntegrationInstanceByClosureRepositoryInterface;
use App\Models\IntegrationInstance;

class FindIntegrationInstanceByClosureRepository implements FindIntegrationInstanceByClosureRepositoryInterface
{
    public function findIntegrationInstanceByClosure(\Closure $closure): ?IntegrationInstance
    {
        $query = IntegrationInstance::query();

        $integrationInstance = $closure($query);

        return $integrationInstance->first();
    }

}
