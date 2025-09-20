<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementDeleteRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementDeleteRepository implements WorkspaceAchievementDeleteRepositoryInterface
{
    public function delete(int $achievementId): int
    {
        return Achievement::destroy($achievementId);
    }
}
