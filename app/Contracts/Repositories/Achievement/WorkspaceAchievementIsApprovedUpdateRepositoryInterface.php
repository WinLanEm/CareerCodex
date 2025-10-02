<?php

namespace App\Contracts\Repositories\Achievement;

interface WorkspaceAchievementIsApprovedUpdateRepositoryInterface
{
    public function update(array $achievementIds): bool;
}
