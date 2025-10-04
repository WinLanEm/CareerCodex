<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIsApprovedUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementIsApprovedUpdateRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;

class AchievementIsApprovedUpdateController extends Controller
{
    public function __construct(
        readonly private AchievementIsApprovedUpdateRepositoryInterface $repository
    )
    {
    }

    public function __invoke(AchievementIsApprovedUpdateRequest $request)
    {
        $userId = auth()->id();
        $achievementIds = $request->get('achievement_ids');
        $res = $this->repository->update($achievementIds,$userId);
        if(!$res) {
            return new MessageResource('ids not found',false,404);
        }
        return response()->noContent();
    }
}
