<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityWithIntegrationDataRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeveloperActivity\DeveloperActivityWrapperResource;
use App\Http\Resources\MessageResource;

class DeveloperActivityFindController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityFindRepositoryInterface $repository,
        readonly private DeveloperActivityWithIntegrationDataRepositoryInterface $withIntegrationDataRepository
    )
    {
    }

    public function __invoke(int $id)
    {
        $developerActivity = $this->repository->find($id);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found',false,404);
        }
        $developerActivityWithService = $this->withIntegrationDataRepository->get($developerActivity,['id', 'service']);
        return new DeveloperActivityWrapperResource($developerActivityWithService);
    }
}
