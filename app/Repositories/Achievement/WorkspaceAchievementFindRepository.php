<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementFindRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementFindRepository implements WorkspaceAchievementFindRepositoryInterface
{
    public function find(int $achievementId): ?Achievement
    {
        return Achievement::find($achievementId);
    }
}
