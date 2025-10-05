<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\DeleteWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class WorkspaceDeleteController extends Controller
{
    public function __construct(
        readonly private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        readonly private DeleteWorkspaceRepositoryInterface $deleteWorkspaceRepository,
    )
    {
    }

    public function __invoke(int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);
        if(!$workspace){
            return new MessageResource('workspace not found',false,404);
        }

        try {
            Gate::authorize('delete', $workspace);
        } catch (AuthorizationException $e) {
            return new MessageResource("This action is unauthorized.", false, 403);
        }

        $this->deleteWorkspaceRepository->delete($id);
        return response()->noContent();
    }
}
