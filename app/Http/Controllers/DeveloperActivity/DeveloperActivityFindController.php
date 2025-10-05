<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityWithIntegrationDataRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeveloperActivity\DeveloperActivityWrapperResource;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

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
        $developerActivity = $this->repository->findWithRelations($id);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found',false,404);
        }

        try {
            Gate::authorize('view', $developerActivity);
        }catch(AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }
        $developerActivityWithService = $this->withIntegrationDataRepository->get($developerActivity,['id', 'service']);
        return new DeveloperActivityWrapperResource($developerActivityWithService);
    }
}
