<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Models\Integration;

class UpdateIntegrationRepository implements UpdateIntegrationInstanceRepositoryInterface
{
    public function update(Integration $serviceConnection, array $data): Integration
    {
        $serviceConnection->update($data);
        return $serviceConnection->fresh();
    }
}
