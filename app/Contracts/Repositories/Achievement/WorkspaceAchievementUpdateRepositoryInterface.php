<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface WorkspaceAchievementUpdateRepositoryInterface
{
    public function update(array $data,Achievement $achievement);
}
