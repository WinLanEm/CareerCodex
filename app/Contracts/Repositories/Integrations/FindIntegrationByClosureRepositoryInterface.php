<?php

namespace App\Contracts\Repositories\Integrations;

use App\Models\Integration;

interface FindIntegrationByClosureRepositoryInterface
{
    public function findIntegrationByClosure(\Closure $closure): ?Integration;
}
