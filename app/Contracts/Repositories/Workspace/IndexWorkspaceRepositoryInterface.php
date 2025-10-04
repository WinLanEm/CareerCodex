<?php

namespace App\Contracts\Repositories\Workspace;

use Illuminate\Pagination\LengthAwarePaginator;

interface IndexWorkspaceRepositoryInterface
{
    public function index(int $page,int $perPage,int $userId): LengthAwarePaginator;
}
