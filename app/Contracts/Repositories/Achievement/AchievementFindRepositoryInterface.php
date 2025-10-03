<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface AchievementFindRepositoryInterface
{
    public function find(int $achievementId, int $userId): ?Achievement;
}
