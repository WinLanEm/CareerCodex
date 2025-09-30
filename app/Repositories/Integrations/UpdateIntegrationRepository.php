<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Models\Integration;

class UpdateIntegrationRepository implements UpdateIntegrationRepositoryInterface
{
    public function update(Integration $serviceConnection, array $data): Integration
    {
        $serviceConnection->update($data);
        return $serviceConnection->fresh();
    }
}
