<?php

namespace App\Contracts\Repositories\DeveloperActivities;

use App\Enums\DeveloperActivityEnum;
use Illuminate\Pagination\LengthAwarePaginator;

interface DeveloperActivityIndexRepositoryInterface
{
    public function index(int $page,int $perPage,int $userId,?DeveloperActivityEnum $type,?bool $isApproved,?string $startDate,?string $endDate): LengthAwarePaginator;
}
