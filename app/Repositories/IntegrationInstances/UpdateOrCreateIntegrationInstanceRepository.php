<?php

namespace App\Repositories\IntegrationInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Models\IntegrationInstance;

class UpdateOrCreateIntegrationInstanceRepository implements UpdateOrCreateIntegrationInstanceRepositoryInterface
{
    public function updateOrCreate(string $serviceId, string $providerInstanceId, bool $hasWebsocket ,string $siteUrl,string $repoName,?string $defaultBranch = null,?array $meta = null): IntegrationInstance
    {
        return IntegrationInstance::updateOrCreate(
            [
                'integration_id' => $serviceId,
                'external_id' => $providerInstanceId,
            ],
            [
                'repository_name' => $repoName,
                'has_websocket' => $hasWebsocket,
                'site_url' => $siteUrl,
                'default_branch' => $defaultBranch,
                'meta' => $meta,
            ]
        );
    }

}
