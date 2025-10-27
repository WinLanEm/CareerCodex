<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use App\Models\Achievement;
use Illuminate\Pagination\LengthAwarePaginator;

class AchievementIndexRepository implements AchievementIndexRepositoryInterface
{
    public function index(int $page, int $perPage, int $userId, bool $isApproved, ?string $startDate, ?string $endDate):LengthAwarePaginator
    {
        return Achievement
            ::with([
                'integrationInstance.integration',
                'user',
            ])
            ->where(function ($query) use ($userId) {
                $query->whereHas('integrationInstance.integration.user', function ($q) use ($userId) {
                    $q->where('id', $userId);
                })
                    ->orWhereHas('user', function ($q) use ($userId) {
                        $q->where('id', $userId);
                    });
            })
                ->when($startDate !== null, function ($query) use ($startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })->when($endDate !== null, function ($query) use ($endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })
            ->where('is_approved', $isApproved)
            ->orderBy('date', 'desc')
            ->paginate(
                $perPage,
                [
                    'id',
                    'title',
                    'description',
                    'date',
                    'is_approved',
                    'result',
                    'skills',
                    'project_name',
                    'is_from_provider',
                    'link'
                ],
                'page',
                $page
            );
    }
}
