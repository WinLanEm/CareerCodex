<?php

namespace App\Http\Controllers\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesIndexRepositoryInterface;
use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AllActivities\ActivitiesIndexRequest;
use App\Http\Resources\AllActivities\ActivitiesCursorResource;

class ActivitiesIndexController extends Controller
{
    public function __construct(
        readonly private ActivitiesIndexRepositoryInterface $activitiesIndexRepository,
    ) {}

    public function __invoke(ActivitiesIndexRequest $request)
    {
        $cursor = $request->cursor;
        $perPage = $request->per_page ?? 10;
        $type = $request->type ?? 'all';
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $userId = auth()->id();

        $result = $this->activitiesIndexRepository->index(
            $userId,
            $perPage,
            $type,
            $cursor,
            $dateFrom,
            $dateTo
        );

        return new ActivitiesCursorResource($result['data'], [
            'next_cursor' => $result['meta']['next_cursor'],
            'has_next_page' => $result['meta']['has_next_page'],
            'per_page' => $result['meta']['per_page'],
        ]);
    }
}
