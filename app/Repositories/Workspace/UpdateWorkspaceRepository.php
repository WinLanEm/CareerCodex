<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\UpdateWorkspaceRepositoryInterface;
use App\Models\Workspace;

class UpdateWorkspaceRepository implements UpdateWorkspaceRepositoryInterface
{
    public function update(Workspace $workspace,array $data): Workspace
    {
        $workspace->update($data);
        return $workspace->fresh();
    }
}
