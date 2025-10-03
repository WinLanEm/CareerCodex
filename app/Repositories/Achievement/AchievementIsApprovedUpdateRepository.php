<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIsApprovedUpdateRepositoryInterface;
use App\Models\Achievement;

class AchievementIsApprovedUpdateRepository implements AchievementIsApprovedUpdateRepositoryInterface
{
    public function update(array $achievementIds): bool
    {
        return Achievement::whereIn('id',$achievementIds)->update(['is_approved' => true]);
    }
}
