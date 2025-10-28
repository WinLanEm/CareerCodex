<?php

namespace App\Repositories\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesPendingApprovalInterface;
use App\Models\Achievement;
use App\Models\DeveloperActivity;
use Illuminate\Database\Eloquent\Collection;

class ActivitiesPendingApprovalRepository implements ActivitiesPendingApprovalInterface
{
    public function getAll(int $userId): Collection
    {
        $devActivities = DeveloperActivity::with('integration')
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                ->orWhereHas('integration', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->where('is_approved', false)
            ->get();

        $achievements = Achievement::with(['integrationInstance.integration'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('integrationInstance.integration', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->where('is_approved', false)
            ->get();

        $combined = $devActivities->concat($achievements)
            ->sortByDesc('created_at')
            ->values();

        return $combined;
    }
}
