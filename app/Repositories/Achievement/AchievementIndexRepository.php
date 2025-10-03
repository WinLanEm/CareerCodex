<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use App\Models\Achievement;
use Illuminate\Pagination\LengthAwarePaginator;

class AchievementIndexRepository implements AchievementIndexRepositoryInterface
{
    public function index(int $page, int $perPage, int $userId, bool $isApproved,?int $workspaceId, ?string $startDate, ?string $endDate):LengthAwarePaginator
    {
        return Achievement
            ::with([
                'integrationInstance' => function ($query) {
                    $query->select('id','integration_id');
                },
                'integrationInstance.integration' => function ($query) {
                    $query->select('id','user_id','service');
                },
                'integrationInstance.integration.user' => function ($query) {
                    $query->select('id');
                }
                ])
                ->whereHas('integrationInstance.integration.user', function ($query) use($userId) {
                    $query->where('id', $userId);
                })
                ->when($startDate !== null, function ($query) use ($startDate) {
                    return $query->whereDate('date', '>=', $startDate);
                })->when($endDate !== null, function ($query) use ($endDate) {
                    return $query->whereDate('date', '<=', $endDate);
                })->when($workspaceId !== null, function ($query) use ($workspaceId) {
                    return $query->where('workspace_id', $workspaceId);
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
                    'project_name'
                ],
                'page',
                $page
            );
    }
}
