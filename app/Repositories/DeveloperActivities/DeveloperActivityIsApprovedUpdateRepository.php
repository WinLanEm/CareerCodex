<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityIsApprovedUpdateRepository implements DeveloperActivityIsApprovedUpdateRepositoryInterface
{
    public function update(array $developerActivityIds): bool
    {
        return DeveloperActivity::whereIn('id',$developerActivityIds)->update(['is_approved' => true]);
    }
}
