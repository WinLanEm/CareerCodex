<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementUpdateRepository implements WorkspaceAchievementUpdateRepositoryInterface
{
    public function update(array $data, Achievement $achievement)
    {
        $achievement->update($data);
        return $achievement->fresh();
    }
}
