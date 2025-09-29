<?php

namespace App\Contracts\Repositories\Integrations;

use App\Models\Integration;

interface UpdateIntegrationRepositoryInterface
{
    public function update(Integration $serviceConnection, array $data): Integration;
}
