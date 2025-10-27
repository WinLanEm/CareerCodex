<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface DeveloperActivityCreateRepositoryInterface
{
    public function create(array $data,int $userId):?DeveloperActivity;
}
