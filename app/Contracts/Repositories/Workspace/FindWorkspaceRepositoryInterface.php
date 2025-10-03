<?php

namespace App\Contracts\Repositories\Workspace;

use App\Models\Workspace;

interface FindWorkspaceRepositoryInterface
{
    public function find(int $id,int $userId): ?Workspace;
}
