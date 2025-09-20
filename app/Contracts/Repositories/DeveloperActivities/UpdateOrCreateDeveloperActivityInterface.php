<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Models\DeveloperActivity;

interface UpdateOrCreateDeveloperActivityInterface
{
    public function updateOrCreateDeveloperActivity(array $data):DeveloperActivity;
}
