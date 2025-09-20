<?php

namespace App\Http\Controllers\Workspace;

use App\Contracts\Repositories\Workspace\DeleteWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;

class WorkspaceDeleteController extends Controller
{
    public function __construct(
        private FindWorkspaceRepositoryInterface $findWorkspaceRepository,
        private DeleteWorkspaceRepositoryInterface $deleteWorkspaceRepository,
    )
    {
    }

    public function __invoke(int $id)
    {
        $workspace = $this->findWorkspaceRepository->find($id);
        if(!$workspace){
            return new MessageResource('workspace not found',false,404);
        }
        $this->deleteWorkspaceRepository->delete($id);
        return response()->noContent();
    }
}
