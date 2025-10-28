<?php

namespace App\Contracts\Repositories\Integrations;

use App\Models\Integration;

interface DeleteIntegrationRepositoryInterface
{
    public function delete(Integration $integration): bool;
}
