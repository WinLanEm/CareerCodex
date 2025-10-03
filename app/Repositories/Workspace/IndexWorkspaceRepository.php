<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\IndexWorkspaceRepositoryInterface;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexWorkspaceRepository implements IndexWorkspaceRepositoryInterface
{
    public function index(int $page, int $perPage,int $userId): LengthAwarePaginator
    {
        return Workspace::where('user_id',$userId)->paginate($perPage, ['name','type','id','description','start_date','end_date'], 'page', $page);
    }
}
