<?php

namespace App\Contracts\Repositories\AllActivities;

use Illuminate\Database\Eloquent\Collection;

interface ActivitiesPendingApprovalInterface
{
    public function getAll(int $userId):Collection;
}
