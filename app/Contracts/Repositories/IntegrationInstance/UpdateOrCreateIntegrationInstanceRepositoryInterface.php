<?php

namespace App\Contracts\Repositories\IntegrationInstance;

use App\Models\IntegrationInstance;

interface UpdateOrCreateIntegrationInstanceRepositoryInterface
{
    public function updateOrCreate(string $serviceId, string $providerInstanceId,bool $hasWebsocket,string $siteUrl = null): IntegrationInstance;
}
