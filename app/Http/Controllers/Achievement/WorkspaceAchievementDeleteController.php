<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementDeleteRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;

class WorkspaceAchievementDeleteController extends Controller
{
    public function __construct(
        private WorkspaceAchievementDeleteRepositoryInterface $workspaceAchievementDeleteRepository,
        private WorkspaceAchievementFindRepositoryInterface $workspaceAchievementFindRepository
    )
    {
    }

    public function __invoke(int $achievementId)
    {
        $achievement = $this->workspaceAchievementFindRepository->find($achievementId);
        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }
        $this->workspaceAchievementDeleteRepository->delete($achievementId);
        return response()->noContent();
    }
}
