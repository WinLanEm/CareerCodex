<?php

namespace App\Contracts\Repositories\Workspace;

use App\Models\Workspace;

interface UpdateWorkspaceRepositoryInterface
{
    public function update(Workspace $workspace,array $data): Workspace;
}
