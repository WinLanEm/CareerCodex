<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Models\Achievement;

class AchievementFindRepository implements AchievementFindRepositoryInterface
{
    public function find(int $achievementId,int $userId): ?Achievement
    {
        return Achievement::where('id', $achievementId)
            ->where(function ($query) use ($userId) {
                $query->whereHas('workspace.user', function ($userQuery) use ($userId) {
                    $userQuery->where('id', '=', $userId);
                })
                    ->orWhereHas('integrationInstance.integration.user', function ($userQuery) use ($userId) {
                        $userQuery->where('id', '=', $userId);
                    });
            })
            ->first();
    }
}
