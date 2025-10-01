<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface DeveloperActivityWithIntegrationDataRepositoryInterface
{
    public function get(DeveloperActivity $developerActivity,array $fields):DeveloperActivity;
}
