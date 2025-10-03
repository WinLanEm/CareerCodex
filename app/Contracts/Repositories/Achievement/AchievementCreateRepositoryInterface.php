<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface AchievementCreateRepositoryInterface
{
    public function create(array $data):Achievement;
}
