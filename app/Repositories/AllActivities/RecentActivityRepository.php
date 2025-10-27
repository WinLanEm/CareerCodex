<?php

namespace App\Repositories\AllActivities;

use App\Contracts\Repositories\AllActivities\RecentActivityRepositoryInterface;
use App\Models\Achievement;
use App\Models\DeveloperActivity;
use Illuminate\Database\Eloquent\Collection;

class RecentActivityRepository implements RecentActivityRepositoryInterface
{
    private int $maxActivities = 5;
    public function getAll(int $userId): Collection
    {
        $activities = DeveloperActivity::where([
            ['user_id', $userId],
            ['is_approved',true]
        ])
            ->latest()
            ->limit($this->maxActivities)
            ->get();

        $achievements = Achievement::where([
            ['user_id', $userId],
            ['is_approved',true]
        ])
            ->latest()
            ->limit($this->maxActivities)
            ->get();

        $combined = $activities->concat($achievements);

        $sorted = $combined->sortByDesc(function ($item) {
            return $item->created_at;
        });

        return $sorted->take($this->maxActivities)->values();
    }
}
