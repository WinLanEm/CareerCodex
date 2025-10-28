<?php

namespace App\Repositories\AllActivities;

use App\Contracts\Repositories\AllActivities\ActivitiesIndexRepositoryInterface;
use App\Models\Achievement;
use App\Models\DeveloperActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivitiesIndexRepository implements ActivitiesIndexRepositoryInterface
{
    public function index(
        int $userId,
        int $perPage = 15,
        string $type = 'all',
        ?string $cursor = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $cursorData = $cursor ? $this->decodeMultiCursor($cursor) : [];

        $loadAchievements = $this->shouldLoadAchievements($type);
        $loadDeveloperActivities = $this->shouldLoadDeveloperActivities($type);

        $achievements = $loadAchievements
            ? $this->getAchievementsWithCursor(
                $userId,
                $cursorData['achievement'] ?? null,
                $perPage,
                $dateFrom,
                $dateTo
            )
            : collect();

        $activities = $loadDeveloperActivities
            ? $this->getDeveloperActivitiesWithCursor(
                $userId,
                $cursorData['developer_activity'] ?? null,
                $perPage,
                $dateFrom,
                $dateTo,
                $type
            )
            : collect();

        $merged = $this->mergeAndSort($achievements, $activities, $perPage);

        $nextCursor = $this->createMultiCursor($merged, $cursorData);

        return [
            'data' => $merged,
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_next_page' => $this->checkForNextPage($merged, $perPage, $nextCursor),
                'per_page' => $perPage,
            ]
        ];
    }

    private function decodeMultiCursor(string $cursor): array
    {
        $parts = explode('|', $cursor);
        $result = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, 'achievement_')) {
                $cursorParts = explode('_', str_replace('achievement_', '', $part));
                if (count($cursorParts) === 2) {
                    $result['achievement'] = [
                        'created_at' => $cursorParts[0],
                        'id' => $cursorParts[1]
                    ];
                }
            } elseif (str_starts_with($part, 'developer_activity_')) {
                $cursorParts = explode('_', str_replace('developer_activity_', '', $part));
                if (count($cursorParts) === 2) {
                    $result['developer_activity'] = [
                        'created_at' => $cursorParts[0],
                        'id' => $cursorParts[1]
                    ];
                }
            }
        }

        return $result;
    }

    private function getAchievementsWithCursor(int $userId,?array $cursorData, int $perPage, ?string $dateFrom, ?string $dateTo): Collection
    {
        $query = Achievement::with(['integrationInstance.integration'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('integrationInstance.integration', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->select('*')
            ->addSelect(DB::raw("'achievement' as entity_type"))
            ->where('is_approved', true);

        if ($dateFrom) $query->where('created_at', '>=', $dateFrom);
        if ($dateTo) $query->where('created_at', '<=', $dateTo . ' 23:59:59');

        if ($cursorData) {
            $cursorDateTime = Carbon::createFromTimestampUTC((int)$cursorData['created_at'])->toDateTimeString();
            $cursorId = (int)$cursorData['id'];

            $query->where(function ($q) use ($cursorDateTime, $cursorId) {
                $q->where('created_at', '<', $cursorDateTime)
                    ->orWhere(function ($q2) use ($cursorDateTime, $cursorId) {
                        $q2->where('created_at', '=', $cursorDateTime)
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($perPage)
            ->get();
    }

    private function getDeveloperActivitiesWithCursor(int $userId,?array $cursorData, int $perPage, ?string $dateFrom, ?string $dateTo, string $activityType): Collection
    {
        $query = DeveloperActivity::with('integration')
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('integration', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->where('is_approved', true)
            ->select('*')
            ->addSelect(DB::raw("'developer_activity' as entity_type"));

        if (in_array($activityType, ['commit', 'pull_request'])) {
            $query->where('type', $activityType);
        }

        if ($dateFrom) $query->where('created_at', '>=', $dateFrom);
        if ($dateTo) $query->where('created_at', '<=', $dateTo . ' 23:59:59');

        if ($cursorData) {
            $cursorDateTime = Carbon::createFromTimestampUTC((int)$cursorData['created_at'])->toDateTimeString();
            $cursorId = (int)$cursorData['id'];

            $query->where(function ($q) use ($cursorDateTime, $cursorId) {
                $q->where('created_at', '<', $cursorDateTime)
                    ->orWhere(function ($q2) use ($cursorDateTime, $cursorId) {
                        $q2->where('created_at', '=', $cursorDateTime)
                            ->where('id', '<', $cursorId);
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($perPage)
            ->get();
    }

    private function mergeAndSort(Collection $achievements, Collection $activities, int $perPage): Collection
    {
        return $achievements->concat($activities)
            ->sortByDesc('created_at')
            ->take($perPage)
            ->values();
    }

    private function createMultiCursor(Collection $mergedData, array $prevCursor = []): ?string
    {
        if ($mergedData->isEmpty()) {
            return null;
        }

        $cursorParts = [];

        $lastAchievement = $mergedData->where('entity_type', 'achievement')->last();
        if ($lastAchievement) {
            $cursorParts[] = "achievement_{$lastAchievement->created_at->timestamp}_{$lastAchievement->id}";
        } elseif (!empty($prevCursor['achievement'])) {
            $c = $prevCursor['achievement'];
            $cursorParts[] = "achievement_{$c['created_at']}_{$c['id']}";
        }

        $lastActivity = $mergedData->where('entity_type', 'developer_activity')->last();
        if ($lastActivity) {
            $cursorParts[] = "developer_activity_{$lastActivity->created_at->timestamp}_{$lastActivity->id}";
        } elseif (!empty($prevCursor['developer_activity'])) {
            $c = $prevCursor['developer_activity'];
            $cursorParts[] = "developer_activity_{$c['created_at']}_{$c['id']}";
        }

        return $cursorParts ? implode('|', $cursorParts) : null;
    }

    private function checkForNextPage(Collection $mergedData, int $perPage, ?string $nextCursor): bool
    {
        if ($mergedData->count() < $perPage || !$nextCursor) {
            return false;
        }

        return true;
    }

    private function shouldLoadAchievements(string $type): bool
    {
        return in_array($type, ['all', 'task']);
    }

    private function shouldLoadDeveloperActivities(string $type): bool
    {
        return in_array($type, ['all', 'commit', 'pull_request']);
    }
}
