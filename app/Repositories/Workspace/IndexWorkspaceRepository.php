<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\IndexWorkspaceRepositoryInterface;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexWorkspaceRepository implements IndexWorkspaceRepositoryInterface
{
    public function index(int $page, int $perPage): LengthAwarePaginator
    {
        return Workspace::paginate($perPage, ['name','type','id'], 'page', $page);
    }
}
