<?php

namespace App\Repositories\IntegrationInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Models\IntegrationInstance;

class UpdateOrCreateIntegrationInstanceRepository implements UpdateOrCreateIntegrationInstanceRepositoryInterface
{
    public function updateOrCreate(string $serviceId, string $providerInstanceId, bool $hasWebsocket ,string $siteUrl = null): IntegrationInstance
    {
        return IntegrationInstance::updateOrCreate(
            [
                'integration_id' => $serviceId,
                'external_id' => $providerInstanceId,
            ],
            [
                'has_websocket' => $hasWebsocket,
                'site_url' => $siteUrl,
            ]
        );
    }

}
