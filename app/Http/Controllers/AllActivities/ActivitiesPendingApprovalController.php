<?php

namespace App\Http\Controllers\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesPendingApprovalInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllActivities\AllActivitiesResource;

class ActivitiesPendingApprovalController extends Controller
{
    public function __construct(
        readonly private ActivitiesPendingApprovalInterface $activitiesPendingApproval,
    )
    {
    }

    public function __invoke()
    {
        $userId = auth()->id();
        $activities = $this->activitiesPendingApproval->getAll($userId);
        return AllActivitiesResource::collection($activities);
    }
}
