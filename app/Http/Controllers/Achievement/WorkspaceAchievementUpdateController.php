<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementFindRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\WorkspaceAchievementUpdateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;

class WorkspaceAchievementUpdateController extends Controller
{
    public function __construct(
        private WorkspaceAchievementFindRepositoryInterface $findRepository,
        private WorkspaceAchievementUpdateRepositoryInterface $updateRepository
    )
    {
    }

    public function __invoke(WorkspaceAchievementUpdateRequest $request,int $achievementId)
    {
        $achievement = $this->findRepository->find($achievementId);
        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }
        $updatedAchievement = $this->updateRepository->update($request->toArray(), $achievement);
        return new AchievementWrapperResource($updatedAchievement);
    }
}
