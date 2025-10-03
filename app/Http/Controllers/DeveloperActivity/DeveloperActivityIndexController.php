<?php

namespace App\Http\Controllers\DeveloperActivity;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIndexRepositoryInterface;
use App\Enums\DeveloperActivityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeveloperActivity\DeveloperActivityIndexRequest;
use App\Http\Resources\DeveloperActivity\IndexDeveloperActivitiesResource;

class DeveloperActivityIndexController extends Controller
{
    public function __construct(
        readonly private DeveloperActivityIndexRepositoryInterface $repository
    )
    {
    }

    private $perPage = 20;
    public function __invoke(DeveloperActivityIndexRequest $request)
    {
        $userId = auth()->id();
        $page = $request->get('page', 1);
        $type = DeveloperActivityEnum::tryFrom($request->get('type'));
        $isApproved = $request->get('is_approved');
        $startAt = $request->get('start_at');
        $endAt = $request->get('end_at');
        $developerActivities = $this->repository->index(
            $page,
            $this->perPage,
            $userId,
            $type,
            $isApproved,
            $startAt,
            $endAt,
        );
        return new IndexDeveloperActivitiesResource($developerActivities);
    }
}
