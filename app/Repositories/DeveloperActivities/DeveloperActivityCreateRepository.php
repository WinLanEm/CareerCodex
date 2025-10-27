<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityCreateRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityCreateRepository implements DeveloperActivityCreateRepositoryInterface
{
    public function create(array $data, int $userId): ?DeveloperActivity
    {
        return DeveloperActivity::create([
            'user_id' => $userId,
            'repository_name' => $data['repository_name'],
            'type' => $data['type'],
            'is_approved' => $data['is_approved'] ?? true,
            'title' => $data['title'],
            'url' => $data['url'] ?? '',
            'completed_at' => $data['completed_at'],
        ]);
    }

}
