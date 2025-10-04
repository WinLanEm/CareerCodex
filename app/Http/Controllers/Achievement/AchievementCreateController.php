<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementCreateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementCreateRequest;
use App\Http\Resources\Achievement\AchievementWrapperResource;
use App\Http\Resources\MessageResource;

class AchievementCreateController extends Controller
{
    public function __construct(
        readonly private AchievementCreateRepositoryInterface $achievementCreateRepository,
    )
    {
    }

    public function __invoke(AchievementCreateRequest $request)
    {
        $userId = auth()->id();
        $achievement = $this->achievementCreateRepository->create($request->toArray(),$userId);
        if(!$achievement){
            return new MessageResource('Achievement create failed',false,401);
        }
        return new AchievementWrapperResource($achievement,true,201);
    }
}
