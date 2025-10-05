<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\CreateWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Http\Resources\Workspace\WorkspaceWrapperResource;

class WorkspaceCreateController extends Controller
{
    public function __construct(
        readonly private CreateWorkspaceRepositoryInterface $workspaceRepository
    )
    {
    }

    public function __invoke(CreateWorkspaceRequest $request)
    {
        $workspace = $this->workspaceRepository->create($request->toArray());
        return new WorkspaceWrapperResource($workspace,true,201);
    }
}
