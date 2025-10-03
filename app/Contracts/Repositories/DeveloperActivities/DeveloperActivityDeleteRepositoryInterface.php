<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface DeveloperActivityDeleteRepositoryInterface
{
    public function delete(DeveloperActivity $developerActivity): bool;
}
