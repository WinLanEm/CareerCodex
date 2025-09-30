<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\Integrations\FindIntegrationByClosureRepositoryInterface;
use App\Models\Integration;

class FindIntegrationByClosureRepository implements FindIntegrationByClosureRepositoryInterface
{
    public function findIntegrationByClosure(\Closure $closure): ?Integration
    {
        $query = Integration::query();

        $integration = $closure($query);

        return $integration->first();
    }
}
