<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityWithIntegrationDataRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityWithIntegrationDataRepository implements DeveloperActivityWithIntegrationDataRepositoryInterface
{
    public function get(DeveloperActivity $developerActivity,array $fields): DeveloperActivity
    {
        return $developerActivity->load([
            'integration' => function ($query) use($fields) {
                $query->select($fields);
            },
        ]);
    }
}
