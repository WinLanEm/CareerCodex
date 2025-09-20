<?php

namespace App\Contracts\Repositories\Achievement;

use App\Models\Achievement;

interface WorkspaceAchievementUpdateOrCreateRepositoryInterface
{
    public function updateOrCreate(array $data):Achievement;
}
