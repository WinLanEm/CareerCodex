<?php

namespace App\Repositories\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesStatRepositoryInterface;
use App\Models\Achievement;
use App\Models\DeveloperActivity;
use Carbon\Carbon;

class ActivitiesStatRepository implements ActivitiesStatRepositoryInterface
{
    public function allStats(int $userId)
    {
        $developerActivities = DeveloperActivity::where([
            ['user_id', $userId],
            ['is_approved', true],
            ['created_at', '>=', now()->subMonth()],
        ])->get();
        $achievements = Achievement::where([
            ['user_id', $userId],
            ['is_approved', true],
            ['created_at', '>=', now()->subMonth()],
        ])->get();

        $periodStart = now()->subDays(6)->startOfDay();

        $recentActivities = $developerActivities->where('created_at', '>=', $periodStart);
        $recentAchievements = $achievements->where('created_at', '>=', $periodStart);

        $daysTemplate = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $periodStart->copy()->addDays($i);
            $daysTemplate[$day->format('Y-m-d')] = [
                'day_name' => $day->format('D'), // 'Thu', 'Fri', 'Sat'.
                'date' => $day->format('Y-m-d'),
                'developer_activities' => 0,
                'achievements' => 0,
            ];
        }

        $groupedActivities = $recentActivities->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

        $groupedAchievements = $recentAchievements->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

        foreach ($daysTemplate as $date => &$dayData) {
            if (isset($groupedActivities[$date])) {
                $dayData['developer_activities'] = $groupedActivities[$date]->count();
            }
            if (isset($groupedAchievements[$date])) {
                $dayData['achievements'] = $groupedAchievements[$date]->count();
            }
        }

        $allActivities = $developerActivities->merge($achievements);

        $activeDates = $allActivities->map(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->unique()->sort()->values();

        $maxStreak = 0;
        $currentStreak = 0;
        $previousDate = null;

        if ($activeDates->isNotEmpty()) {
            foreach ($activeDates as $date) {
                if (is_null($previousDate)) {
                    $currentStreak = 1;
                }
                elseif (Carbon::parse($date)->isSameDay(Carbon::parse($previousDate)->addDay())) {
                    $currentStreak++;
                }
                else {
                    $maxStreak = max($maxStreak, $currentStreak);
                    $currentStreak = 1;
                }
                $previousDate = $date;
            }
        }

        $maxStreak = max($maxStreak, $currentStreak);

        return [
            'per_month' => [
                'developer_activities' => $developerActivities->count(),
                'achievements' => $achievements->count(),
            ],
            'per_week' => [
                'developer_activities' => $recentActivities->count(),
                'achievements' => $recentAchievements->count(),
            ],
            'per_days' => array_values($daysTemplate),
            'streak' => $maxStreak,
        ];
    }
}
