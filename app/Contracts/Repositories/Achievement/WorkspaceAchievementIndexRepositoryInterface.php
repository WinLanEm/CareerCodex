<?php

namespace App\Contracts\Repositories\Achievement;

use Illuminate\Pagination\LengthAwarePaginator;

interface WorkspaceAchievementIndexRepositoryInterface
{
    public function index(int $page,int $perPage,int $workspaceId):LengthAwarePaginator;
}
