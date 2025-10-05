<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\UpdateWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\Workspace\WorkspaceWrapperResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class WorkspaceUpdateController extends Controller
{
    public function __construct(
        readonly private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        readonly private UpdateWorkspaceRepositoryInterface $updateWorkspaceRepository
    )
    {
    }

    public function __invoke(UpdateWorkspaceRequest $request, int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);
        if(!$workspace){
            return new MessageResource("workspace not found",false,404);
        }

        try {
            Gate::authorize('update', $workspace);
        } catch (AuthorizationException $e) {
            return new MessageResource("This action is unauthorized.", false, 403);
        }

        $updatedWorkspace = $this->updateWorkspaceRepository->update($workspace,$request->toArray());
        return new WorkspaceWrapperResource($updatedWorkspace);
    }
}
