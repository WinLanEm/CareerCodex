<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Models\Workspace;

class FindWorkspaceRepository implements FindWorkspaceRepositoryInterface
{
    public function find(int $id): ?Workspace
    {
        return Workspace::find($id);
    }
}
