<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class AchievementFindController extends Controller
{
    public function __construct(
        readonly private AchievementFindRepositoryInterface $achievementFindRepository
    )
    {
    }

    public function __invoke(int $achievementId)
    {
        $achievement = $this->achievementFindRepository->findWithRelations($achievementId);
        if(!$achievement) {
            return new MessageResource('achievement not found',false,404);
        }

        try{
            Gate::authorize('view', $achievement);
        }catch(AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }

        return new AchievementWrapperResource($achievement);
    }
}
