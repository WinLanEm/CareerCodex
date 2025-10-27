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
        readonly private CacheRepositoryInterface $cacheRepository
    )
    {
    }

    public function __invoke()
    {
        $userId = auth()->id();
        $key = 'activities:stats:user:' . $userId;
        $activities = $this->cacheRepository->remember($key, function () use ($userId) {
             return $this->activitiesStatRepository->allStats($userId);
        },600);
        return new ActivitiesStatResource($activities);
    }
}
