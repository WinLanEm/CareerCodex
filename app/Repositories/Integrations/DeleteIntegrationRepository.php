<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\Integrations\DeleteIntegrationRepositoryInterface;
use App\Models\Integration;

class DeleteIntegrationRepository implements DeleteIntegrationRepositoryInterface
{
    public function delete(Integration $integration): bool
    {
        return (bool) $integration->delete();
    }
}
