<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;

class AchievementFindController extends Controller
{
    public function __construct(
        private AchievementFindRepositoryInterface $achievementFindRepository
    )
    {
    }

    public function __invoke(int $achievementId)
    {
        $achievement = $this->achievementFindRepository->find($achievementId);
        if(!$achievement) {
            return new MessageResource('achievement not found',false,404);
        }
        return new AchievementWrapperResource($achievement);
    }
}
