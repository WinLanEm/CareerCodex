<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementUpdateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;

class AchievementUpdateController extends Controller
{
    public function __construct(
        private AchievementFindRepositoryInterface            $findRepository,
        private AchievementUpdateRepositoryInterface $updateRepository
    )
    {
    }

    public function __invoke(AchievementUpdateRequest $request, int $achievementId)
    {
        $achievement = $this->findRepository->find($achievementId);
        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }
        $updatedAchievement = $this->updateRepository->update($request->toArray(), $achievement);
        return new AchievementWrapperResource($updatedAchievement);
    }
}
