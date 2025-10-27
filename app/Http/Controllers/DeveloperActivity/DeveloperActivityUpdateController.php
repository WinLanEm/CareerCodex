<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityUpdateRequest;
use App\Http\Resources\DeveloperActivity\DeveloperActivityWrapperResource;
use App\Http\Resources\MessageResource;
use App\Repositories\DeveloperActivities\DeveloperActivityFindRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeveloperActivityUpdateController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityFindRepository $findRepository,
        readonly private DeveloperActivityUpdateRepositoryInterface $updateRepository
    )
    {
    }

    public function __invoke(DeveloperActivityUpdateRequest $request,int $id)
    {
        $developerActivity = $this->findRepository->findWithRelations($id);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found',false,404);
        }

        try {
            Gate::authorize('update', $developerActivity);
        }catch(AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }

        $updatedDeveloperActivity = $this->updateRepository->update($developerActivity, $request->toArray());
        return new DeveloperActivityWrapperResource($updatedDeveloperActivity);
    }
}
