<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use App\Contracts\Repositories\Workspace\IndexWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\IndexWorkspaceRequest;
use App\Http\Resources\Workspace\IndexWorkspaceResource;

class WorkspaceIndexController extends Controller
{
    private int $perPage = 20;
    public function __construct(
        readonly private IndexWorkspaceRepositoryInterface $workspaceRepository,
        readonly private CacheRepositoryInterface $cacheRepository
    )
    {
    }

    public function __invoke(IndexWorkspaceRequest $request)
    {
        $userId = auth()->id();
        $page = $request->get('page', 1);
        $queryString = http_build_query($request->validated());
        $cacheKey = "achievements:user:{$userId}:$queryString";
        $workspaces = $this->cacheRepository->remember($cacheKey,function () use ($userId, $page) {
            return $this->workspaceRepository->index($page, $this->perPage,$userId);
        });
        return new IndexWorkspaceResource($workspaces);
    }
}
