<?php

namespace App\Contracts\Repositories\Workspace;

use App\Models\Workspace;

interface CreateWorkspaceRepositoryInterface
{
    public function create(array $data): ?Workspace;
}
