<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Models\Achievement;

class AchievementFindRepository implements AchievementFindRepositoryInterface
{
    public function find(int $achievementId): ?Achievement
    {
        return Achievement::find($achievementId);
    }
}
