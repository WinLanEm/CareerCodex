<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface WorkspaceAchievementFindRepositoryInterface
{
    public function find(int $achievementId): ?Achievement;
}
