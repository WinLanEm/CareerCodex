<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityIsApprovedUpdateRepository implements DeveloperActivityIsApprovedUpdateRepositoryInterface
{
    public function update(array $developerActivityIds,int $userId): bool
    {
        if (empty($developerActivityIds)) {
            return false;
        }

        $ownedCount = DeveloperActivity::whereIn('id', $developerActivityIds)
            ->whereHas('integration.user', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->count();

        if ($ownedCount !== count($developerActivityIds)) {
            return false;
        }

        DeveloperActivity::whereIn('id', $developerActivityIds)->update(['is_approved' => true]);

        return true;
    }
}
