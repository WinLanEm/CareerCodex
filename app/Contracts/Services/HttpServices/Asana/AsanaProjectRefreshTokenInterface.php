<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Models\Integration;

interface AsanaProjectRefreshTokenInterface
{
    public function refreshAccessToken(Integration $integration):bool;
}
