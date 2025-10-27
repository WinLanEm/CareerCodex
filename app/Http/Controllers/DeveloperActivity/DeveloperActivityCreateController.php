<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityCreateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityCreateRequest;
use App\Http\Resources\DeveloperActivity\DeveloperActivityWrapperResource;
use App\Http\Resources\MessageResource;

class DeveloperActivityCreateController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityCreateRepositoryInterface $repository
    )
    {
    }

    public function __invoke(DeveloperActivityCreateRequest $request)
    {
        $userId = auth()->id();
        $developerActivity = $this->repository->create($request->toArray(), $userId);
        if(!$developerActivity){
            return new MessageResource('DeveloperActivity create failed',false,401);
        }
        return new DeveloperActivityWrapperResource($developerActivity,true,201);
    }
}
