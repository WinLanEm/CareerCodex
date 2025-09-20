<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementCreateRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\WorkspaceAchievementCreateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;

class WorkspaceAchievementCreateController extends Controller
{
    public function __construct(
        private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        private WorkspaceAchievementCreateRepositoryInterface $workspaceAchievementCreateRepository,
    )
    {
    }

    public function __invoke(WorkspaceAchievementCreateRequest $request,int $workspaceId)
    {
        $workspace = $this->findWorkspaceRepository->find($workspaceId);
        if(!$workspace){
            return new MessageResource('workspace not found',false,404);
        }
        $achievement = $this->workspaceAchievementCreateRepository->create($request->toArray(),$workspaceId);
        return new AchievementWrapperResource($achievement,true,201);
    }
}
