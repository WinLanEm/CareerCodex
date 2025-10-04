<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIsApprovedUpdateRepositoryInterface;
use App\Models\Achievement;

class AchievementIsApprovedUpdateRepository implements AchievementIsApprovedUpdateRepositoryInterface
{
    public function update(array $achievementIds,int $userId): bool
    {
        if (empty($achievementIds)) {
            return false;
        }

        $ownedCount = Achievement::whereIn('id', $achievementIds)
            ->where(function ($query) use ($userId) {
                $query->whereHas('workspace.user', function ($q) use ($userId) {
                    $q->where('id', $userId);
                })
                    ->orWhereHas('integrationInstance.integration.user', function ($q) use ($userId) {
                        $q->where('id', $userId);
                    });
            })
            ->count();

        if ($ownedCount !== count($achievementIds)) {
            return false;
        }

        Achievement::whereIn('id', $achievementIds)->update(['is_approved' => true]);

        return true;
    }
}
