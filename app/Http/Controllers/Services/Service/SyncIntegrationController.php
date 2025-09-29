<?php

namespace App\Http\Controllers\Services\Service;

use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Http\Controllers\Controller;

class SyncIntegrationController extends Controller
{
    public function __construct(
        private GetIntegrationInstanceStrategyInterface $getIntegrationInstanceStrategy,
    )
    {
    }

    public function __invoke()
    {
        $user = auth()->user();
        $integrations = $user->integrations;
        foreach ($integrations as $integration) {
            $this->getIntegrationInstanceStrategy->getInstance($integration);
        }
        return response()->noContent();
    }
}
