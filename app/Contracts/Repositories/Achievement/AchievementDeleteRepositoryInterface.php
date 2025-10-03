<?php

namespace App\Contracts\Repositories\Achievement;


interface AchievementDeleteRepositoryInterface
{
    public function delete(int $achievementId): int;
}
