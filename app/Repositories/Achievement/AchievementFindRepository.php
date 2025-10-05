<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Models\Achievement;

class AchievementFindRepository implements AchievementFindRepositoryInterface
{
    public function find(int $achievementId): ?Achievement
    {
        return Achievement::where('id', $achievementId)->first();
    }

    public function findWithRelations(int $achievementId): ?Achievement
    {
        return Achievement::with([
            'workspace.user',
            'integrationInstance.integration.user',
        ])->find($achievementId);
    }
}
