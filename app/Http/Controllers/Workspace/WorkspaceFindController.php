<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Http\Resources\Workspace\WorkspaceWrapperResource;

class WorkspaceFindController extends Controller
{
    public function __construct(
        private FindWorkspaceRepositoryInterface $findWorkspaceRepository
    )
    {
    }

    public function __invoke(int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);
        if (!$workspace) {
            return new MessageResource("Workspace not found",false,404);
        }
        return new WorkspaceWrapperResource($workspace);
    }
}
