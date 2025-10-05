<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementDeleteRepositoryInterface;
use App\Models\Achievement;

class AchievementDeleteRepository implements AchievementDeleteRepositoryInterface
{
    public function delete(Achievement $achievement): int
    {
        return $achievement->delete();
    }
}
