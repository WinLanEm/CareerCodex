<?php

namespace App\Contracts\Repositories\Achievement;


use App\Models\Achievement;

interface AchievementDeleteRepositoryInterface
{
    public function delete(Achievement $achievement): int;
}
