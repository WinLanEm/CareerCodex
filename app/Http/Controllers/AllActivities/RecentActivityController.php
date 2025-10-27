<?php

namespace App\Http\Controllers\AllActivities;

use App\Contracts\Repositories\AllActivities\RecentActivityRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllActivities\AllActivitiesResource;

class RecentActivityController extends Controller
{
    public function __construct(
        readonly private RecentActivityRepositoryInterface $recentActivityRepository
    )
    {
    }

    public function __invoke()
    {
        $userId = auth()->id();
        $activities = $this->recentActivityRepository->getAll($userId);
        return AllActivitiesResource::collection($activities);
    }
}
