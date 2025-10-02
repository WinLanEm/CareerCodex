<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityIsApprovedUpdateRequest;
use App\Http\Resources\MessageResource;

class DeveloperActivityIsApprovedUpdateController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityIsApprovedUpdateRepositoryInterface $developerActivityIsApprovedUpdateRepository
    )
    {
    }

    public function __invoke(DeveloperActivityIsApprovedUpdateRequest $request)
    {
        $developerActivityIds = $request->get('developer_activity_ids');
        $res = $this->developerActivityIsApprovedUpdateRepository->update($developerActivityIds);
        if(!$res) {
            return new MessageResource('ids not found',false,404);
        }
        return response()->noContent();
    }
}
