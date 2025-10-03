<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityFindRepository implements DeveloperActivityFindRepositoryInterface
{
    public function find(int $id,int $userId): ?DeveloperActivity
    {
        return DeveloperActivity::where('id', $id)
            ->whereHas('integration.user', function ($query) use ($userId) {
                $query->where('id', $userId);
            })
            ->first();
    }
}
