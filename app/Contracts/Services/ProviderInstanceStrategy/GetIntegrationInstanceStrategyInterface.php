<?php

namespace App\Contracts\Services\ProviderInstanceStrategy;

use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Laravel\Socialite\Contracts\User;

interface GetIntegrationInstanceStrategyInterface
{
    public function getInstance(Integration $integration, bool $isFirstRun = false):void;
}
