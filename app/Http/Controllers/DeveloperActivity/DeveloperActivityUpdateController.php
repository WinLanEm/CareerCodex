<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityUpdateRequest;
use App\Http\Resources\DeveloperActivity\DeveloperActivityWrapperResource;
use App\Http\Resources\MessageResource;
use App\Repositories\DeveloperActivities\DeveloperActivityFindRepository;
use Illuminate\Http\Request;

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
        $userId = auth()->id();
        $title = $request->get('title');
        $isApproved = $request->get('is_approved');
        $developerActivity = $this->findRepository->find($id,$userId);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity not found',false,404);
        }
        $updatedDeveloperActivity = $this->updateRepository->update($developerActivity, ['title' => $title, 'is_approved' => $isApproved]);
        return new DeveloperActivityWrapperResource($updatedDeveloperActivity);
    }
}
