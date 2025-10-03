<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementCreateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementCreateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;

class AchievementCreateController extends Controller
{
    public function __construct(
        private AchievementCreateRepositoryInterface $achievementCreateRepository,
    )
    {
    }

    public function __invoke(AchievementCreateRequest $request, int $workspaceId)
    {
        $achievement = $this->achievementCreateRepository->create($request->toArray());
        return new AchievementWrapperResource($achievement,true,201);
    }
}
