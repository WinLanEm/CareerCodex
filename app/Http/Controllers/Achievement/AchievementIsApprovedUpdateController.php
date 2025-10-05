<?php

namespace App\Http\Controllers\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIsApprovedUpdateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\AchievementIsApprovedUpdateRequest;
use App\Http\Resources\MessageResource;
use App\Models\Achievement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AchievementIsApprovedUpdateController extends Controller
{
    public function __construct(
        readonly private AchievementIsApprovedUpdateRepositoryInterface $repository
    )
    {
    }

    public function __invoke(AchievementIsApprovedUpdateRequest $request)
    {
        $achievementIds = $request->get('achievement_ids');

        try {
            Gate::authorize('approveMultiple',[Achievement::class, $achievementIds]);
        } catch (AuthorizationException $e) {
            return new MessageResource('This action is unauthorized.', false, 403);
        }


        $this->repository->update($achievementIds);

        return response()->noContent();
    }
}
