<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementIsApprovedUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\WorkspaceAchievementIsApprovedUpdateRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;

class WorkspaceAchievementIsApprovedUpdateController extends Controller
{
    public function __construct(
        readonly private WorkspaceAchievementIsApprovedUpdateRepositoryInterface $repository
    )
    {
    }

    public function __invoke(WorkspaceAchievementIsApprovedUpdateRequest $request)
    {
        $achievementIds = $request->get('achievement_ids');
        $res = $this->repository->update($achievementIds);
        if(!$res) {
            return new MessageResource('ids not found',false,404);
        }
        return response()->noContent();
    }
}
