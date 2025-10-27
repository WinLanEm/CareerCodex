<?php

namespace App\Contracts\Repositories\AllActivities;

use Illuminate\Database\Eloquent\Collection;

interface RecentActivityRepositoryInterface
{
    public function getAll(int $userId):Collection;
}
