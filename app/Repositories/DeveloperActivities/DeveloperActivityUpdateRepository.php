<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityUpdateRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityUpdateRepository implements DeveloperActivityUpdateRepositoryInterface
{
    public function update(DeveloperActivity $activity, array $data): DeveloperActivity
    {
        $data['url'] = $data['url'] ?? '';
        $activity->update($data);
        return $activity->fresh();
    }
}
