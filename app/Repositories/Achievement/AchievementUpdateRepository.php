<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementUpdateRepositoryInterface;
use App\Models\Achievement;

class AchievementUpdateRepository implements AchievementUpdateRepositoryInterface
{
    public function update(array $data, Achievement $achievement)
    {
        $achievement->update($data);
        return $achievement->fresh();
    }
}
