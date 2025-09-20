<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementIndexRepositoryInterface;
use App\Models\Achievement;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkspaceAchievementIndexRepository implements WorkspaceAchievementIndexRepositoryInterface
{
    public function index(int $page, int $perPage,int $workspaceId): LengthAwarePaginator
    {
        return Achievement::where('workspace_id', $workspaceId)
            ->paginate(
                $perPage,
                ['title', 'hours_spent', 'date', 'skills'],
                'page',
                $page
            );
    }
}
