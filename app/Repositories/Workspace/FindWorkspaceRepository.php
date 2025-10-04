<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Models\Workspace;

class FindWorkspaceRepository implements FindWorkspaceRepositoryInterface
{
    public function find(int $id,int $userId): ?Workspace
    {
        $workspace = Workspace::find($id);
        if(!$workspace || $workspace->user_id != $userId) {
            return null;
        }
        return $workspace;
    }
}
