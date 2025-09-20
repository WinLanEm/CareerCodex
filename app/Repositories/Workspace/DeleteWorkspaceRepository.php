<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\DeleteWorkspaceRepositoryInterface;
use App\Models\Workspace;

class DeleteWorkspaceRepository implements DeleteWorkspaceRepositoryInterface
{
    public function delete(int $workspaceId): int
    {
        return Workspace::destroy($workspaceId);
    }

}
