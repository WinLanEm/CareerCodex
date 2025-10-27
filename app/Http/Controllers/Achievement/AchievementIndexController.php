<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementIndexRequest;
use App\Http\Resources\Achievement\IndexAchievementResource;

class AchievementIndexController extends Controller
{
    private int $perPage = 20;
    public function __construct(
        readonly private AchievementIndexRepositoryInterface $repository,
        readonly private CacheRepositoryInterface $cacheRepository
    )
    {
    }

    public function __invoke(AchievementIndexRequest $request)
    {
        $userId = auth()->id();
        $page = $request->get('page', 1);
        $isApproved = $request->get('is_approved');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $queryString = http_build_query($request->validated());
        $cacheKey = "achievements:user:{$userId}:$queryString";
        $achievements = $this->cacheRepository->remember($cacheKey, function () use ($userId, $page, $isApproved, $startDate, $endDate) {
            return $this->repository->index($page,$this->perPage,$userId,$isApproved,$startDate,$endDate);
        });
        return new IndexAchievementResource($achievements);
    }
}
