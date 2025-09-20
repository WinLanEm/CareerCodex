<?php

namespace App\Contracts\Repositories\IntegrationInstance;

use App\Models\Integration;

interface UpdateIntegrationInstanceRepositoryInterface
{
    public function update(Integration $serviceConnection, array $data): Integration;
}
