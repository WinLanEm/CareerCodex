<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementIsApprovedUpdateRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementIsApprovedUpdateRepository implements WorkspaceAchievementIsApprovedUpdateRepositoryInterface
{
    public function update(array $achievementIds): bool
    {
        return Achievement::whereIn('id',$achievementIds)->update(['is_approved' => true]);
    }
}
