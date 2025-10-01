<?php

namespace App\Repositories\DeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityDeleteRepositoryInterface;
use App\Models\DeveloperActivity;

class DeveloperActivityDeleteRepository implements DeveloperActivityDeleteRepositoryInterface
{
    public function delete(DeveloperActivity $developerActivity): bool
    {
        return $developerActivity->delete();
    }
}
