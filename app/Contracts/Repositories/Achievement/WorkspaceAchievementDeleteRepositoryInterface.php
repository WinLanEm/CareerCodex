<?php

namespace App\Contracts\Repositories\Achievement;


interface WorkspaceAchievementDeleteRepositoryInterface
{
    public function delete(int $achievementId): int;
}
