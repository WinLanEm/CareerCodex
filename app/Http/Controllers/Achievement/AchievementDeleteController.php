<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementDeleteRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;

class AchievementDeleteController extends Controller
{
    public function __construct(
        readonly private AchievementDeleteRepositoryInterface $workspaceAchievementDeleteRepository,
        readonly private AchievementFindRepositoryInterface   $workspaceAchievementFindRepository
    )
    {
    }

    public function __invoke(int $achievementId)
    {
        $userId = auth()->id();
        $achievement = $this->workspaceAchievementFindRepository->find($achievementId,$userId);
        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }
        $this->workspaceAchievementDeleteRepository->delete($achievementId);
        return response()->noContent();
    }
}
