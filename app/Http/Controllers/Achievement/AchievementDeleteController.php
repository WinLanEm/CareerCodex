<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementDeleteRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

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
        $achievement = $this->workspaceAchievementFindRepository->findWithRelations($achievementId);
        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }

        try {
            Gate::authorize('delete', $achievement);
        }catch (AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }
        $this->workspaceAchievementDeleteRepository->delete($achievement);
        return response()->noContent();
    }
}
