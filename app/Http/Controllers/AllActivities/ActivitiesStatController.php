<?php

namespace App\Http\Controllers\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesStatRepositoryInterface;
use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllActivities\ActivitiesStatResource;

class ActivitiesStatController extends Controller
{
    public function __construct(
        readonly private ActivitiesStatRepositoryInterface $activitiesStatRepository,
    )
    {
    }

    public function __invoke()
    {
        $userId = auth()->id();

        $activities = $this->activitiesStatRepository->allStats($userId);

        return new ActivitiesStatResource($activities);
    }
}
