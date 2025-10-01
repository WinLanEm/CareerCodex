<?php

namespace App\Contracts\Repositories\Achievement;

interface AchievementIndexRepositoryInterface
{
    public function index(int $page,int $perPage,int $userId,bool $isApproved,?string $startDate,?string $endDate);
}
