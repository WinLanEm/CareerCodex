<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityIsApprovedUpdateRequest;
use App\Http\Resources\MessageResource;
use App\Models\DeveloperActivity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

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
        try {
            Gate::authorize('approveMultiple',[DeveloperActivity::class, $developerActivityIds]);
        }catch (AuthorizationException $e){
            return new MessageResource('This action is unauthorized.',false,403);
        }
        $this->developerActivityIsApprovedUpdateRepository->update($developerActivityIds);

        return response()->noContent();
    }
}
