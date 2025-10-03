<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface AchievementUpdateRepositoryInterface
{
    public function update(array $data,Achievement $achievement);
}
