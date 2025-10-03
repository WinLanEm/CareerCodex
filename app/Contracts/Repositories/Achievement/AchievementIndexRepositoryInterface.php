<?php

namespace App\Contracts\Repositories\Achievement;

use Illuminate\Pagination\LengthAwarePaginator;

interface AchievementIndexRepositoryInterface
{
    public function index(int $page,int $perPage,int $userId,bool $isApproved,?int $workspaceId,?string $startDate,?string $endDate):LengthAwarePaginator;
}
