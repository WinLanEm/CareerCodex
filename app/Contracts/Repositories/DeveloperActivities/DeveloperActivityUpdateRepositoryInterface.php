<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface DeveloperActivityUpdateRepositoryInterface
{
    public function update(DeveloperActivity $activity,array $data): DeveloperActivity;
}
