<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementDeleteRepositoryInterface;
use App\Models\Achievement;

class AchievementDeleteRepository implements AchievementDeleteRepositoryInterface
{
    public function delete(int $achievementId): int
    {
        return Achievement::destroy($achievementId);
    }
}
