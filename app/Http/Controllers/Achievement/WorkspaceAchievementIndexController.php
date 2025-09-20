<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementIndexRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\WorkspaceAchievementIndexRequest;
use App\Http\Resources\Achievement\IndexAchievementResource;
use App\Http\Resources\MessageResource;

class WorkspaceAchievementIndexController extends Controller
{
    private $perPage = 20;
    public function __construct(
        private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        private WorkspaceAchievementIndexRepositoryInterface $workspaceAchievementIndexRepository,
    )
    {
    }

    public function __invoke(WorkspaceAchievementIndexRequest $request,int $workspaceId)
    {
        $page = $request->get('page', 1);
        $workspace = $this->findWorkspaceRepository->find($workspaceId);
        if(!$workspace) {
            return new MessageResource('workspace not found',false,404);
        }
        $achievement = $this->workspaceAchievementIndexRepository->index($page,$this->perPage,$workspaceId);
        return new IndexAchievementResource($achievement);
    }
}
