<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\UpdateWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\Workspace\WorkspaceWrapperResource;

class WorkspaceUpdateController extends Controller
{
    public function __construct(
        private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        private UpdateWorkspaceRepositoryInterface $updateWorkspaceRepository
    )
    {
    }

    public function __invoke(UpdateWorkspaceRequest $request, int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);
        if(!$workspace){
            return new MessageResource("workspace not found",false,404);
        }
        $updatedWorkspace = $this->updateWorkspaceRepository->update($workspace,$request->toArray());
        return new WorkspaceWrapperResource($updatedWorkspace);
    }
}
