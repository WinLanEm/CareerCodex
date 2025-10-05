<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Http\Resources\Workspace\WorkspaceWrapperResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class WorkspaceFindController extends Controller
{
    public function __construct(
        readonly private FindWorkspaceRepositoryInterface $findWorkspaceRepository
    )
    {
    }

    public function __invoke(int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);

        if (!$workspace) {
            return new MessageResource("Workspace not found",false,404);
        }

        try {
            Gate::authorize('view', $workspace);
        } catch (AuthorizationException $e) {
            return new MessageResource("This action is unauthorized.", false, 403);
        }

        return new WorkspaceWrapperResource($workspace);
    }
}
