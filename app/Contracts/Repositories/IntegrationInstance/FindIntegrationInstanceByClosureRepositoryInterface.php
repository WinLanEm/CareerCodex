<?php

namespace App\Contracts\Repositories\IntegrationInstance;

use App\Models\IntegrationInstance;

interface FindIntegrationInstanceByClosureRepositoryInterface
{
    public function findIntegrationInstanceByClosure(\Closure $closure): ?IntegrationInstance;
}
