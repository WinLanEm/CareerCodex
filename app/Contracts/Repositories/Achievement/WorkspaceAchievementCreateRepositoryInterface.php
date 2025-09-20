<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface WorkspaceAchievementCreateRepositoryInterface
{
    public function create(array $data,int $workspaceId):Achievement;
}
