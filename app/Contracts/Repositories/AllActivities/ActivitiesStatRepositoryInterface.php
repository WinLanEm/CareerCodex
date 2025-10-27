<?php

namespace App\Contracts\Repositories\AllActivities;

interface ActivitiesStatRepositoryInterface
{
    public function allStats(int $userId);
}
