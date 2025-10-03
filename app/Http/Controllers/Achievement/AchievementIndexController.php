<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementIndexRequest;
use App\Http\Resources\Achievement\IndexAchievementResource;

class AchievementIndexController extends Controller
{
    private int $perPage = 20;
    public function __construct(
        readonly private AchievementIndexRepositoryInterface $repository,
    )
    {
    }

    public function __invoke(AchievementIndexRequest $request)
    {
        $userId = auth()->id();
        $page = $request->get('page', 1);
        $isApproved = $request->get('is_approved');
        $workspaceId = $request->get('workspace_id');
        $achievements = $this->repository->index($page,$this->perPage,$userId,$isApproved,$workspaceId,null,null);
        return new IndexAchievementResource($achievements);
    }
}
