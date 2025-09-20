<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\DeveloperActivity;

class UpdateOrCreateDeveloperActivity implements UpdateOrCreateDeveloperActivityInterface
{
    public function updateOrCreateDeveloperActivity(array $data):DeveloperActivity
    {
        return DeveloperActivity::updateOrCreate(
            [
                'integration_id' => $data['integration_id'],
                'type' => $data['type'],
                'external_id' => $data['external_id'],
            ],
            [
                'repository_name' => $data['repository_name'],
                'title' => $data['title'],
                'url' => $data['url'],
                'completed_at' => $data['completed_at'],
                'additions' => $data['additions'],
                'deletions' => $data['deletions'],
            ]
        );
    }
}
