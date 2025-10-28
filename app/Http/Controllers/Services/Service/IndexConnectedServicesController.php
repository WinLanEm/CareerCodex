<?php

namespace App\Http\Controllers\Services\Service;

use App\Contracts\Repositories\Integrations\GetUserIntegrationsRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\IndexIntegrationResource;

class IndexConnectedServices extends Controller
{
    public function __construct(
        readonly private GetUserIntegrationsRepositoryInterface $getUserIntegrationsRepository
    )
    {
    }

    public function __invoke()
    {
        $userId = auth()->id();
        $services = $this->getUserIntegrationsRepository->getByUserId($userId);
        return IndexIntegrationResource::collection($services);
    }
}
