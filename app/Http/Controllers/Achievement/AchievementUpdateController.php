<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementUpdateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;


class AchievementUpdateController extends Controller
{
    public function __construct(
        readonly private AchievementFindRepositoryInterface            $findRepository,
        readonly private AchievementUpdateRepositoryInterface $updateRepository
    )
    {
    }

    public function __invoke(AchievementUpdateRequest $request, int $achievementId)
    {
        $achievement = $this->findRepository->findWithRelations($achievementId);

        if(!$achievement){
            return new MessageResource('achievement not found',false,404);
        }

        try {
            Gate::authorize('update', $achievement);
            $updatedAchievement = $this->updateRepository->update($request->toArray(), $achievement);
        }catch(AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }

        return new AchievementWrapperResource($updatedAchievement);
    }
}
