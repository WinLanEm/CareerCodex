<?php

namespace App\Http\Controllers\Services\Service;

use App\Contracts\Repositories\Integrations\DeleteIntegrationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Integration;

class DeleteServiceController extends Controller
{
    public function __construct(
        readonly private DeleteIntegrationRepositoryInterface $deleteIntegrationRepository,
    )
    {
    }

    public function __invoke(Integration $integration)
    {
        $res = $this->deleteIntegrationRepository->delete($integration);
        if(!$res){
            return new MessageResource('Integration not deleted',false,500);
        }
        return response()->noContent();
    }
}
