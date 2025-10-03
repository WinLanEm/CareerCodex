<?php

namespace App\Contracts\Repositories\Achievement;

interface AchievementIsApprovedUpdateRepositoryInterface
{
    public function update(array $achievementIds,int $userId): bool;
}
