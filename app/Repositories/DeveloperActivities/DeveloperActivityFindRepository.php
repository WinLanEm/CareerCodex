<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityFindRepository implements DeveloperActivityFindRepositoryInterface
{
    public function find(int $id): ?DeveloperActivity
    {
        return DeveloperActivity::find($id);
    }
}
