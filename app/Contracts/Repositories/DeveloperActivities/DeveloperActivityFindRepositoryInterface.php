<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface DeveloperActivityFindRepositoryInterface
{
    public function find(int $id,int $userId):?DeveloperActivity;
}
