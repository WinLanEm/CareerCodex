<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface AchievementUpdateOrCreateRepositoryInterface
{
    public function updateOrCreate(array $data):Achievement;
}
