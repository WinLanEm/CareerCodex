<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Models\Integration;

interface AsanaProjectRefreshTokenInterface
{
    public function refreshAccessToken(Integration $integration):bool;
}
