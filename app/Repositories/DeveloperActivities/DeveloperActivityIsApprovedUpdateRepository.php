<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityIsApprovedUpdateRepository implements DeveloperActivityIsApprovedUpdateRepositoryInterface
{
    public function update(array $developerActivityIds): int
    {
        if (empty($developerActivityIds)) {
            return false;
        }

        return DeveloperActivity::whereIn('id', $developerActivityIds)->update(['is_approved' => true]);
    }
}
