<?php

namespace App\Contracts\Repositories\Workspace;

interface DeleteWorkspaceRepositoryInterface
{
    public function delete(int $workspaceId): int;
}
