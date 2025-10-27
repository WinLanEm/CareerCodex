<?php

namespace App\Contracts\Repositories\AllActivities;

interface ActivitiesIndexRepositoryInterface
{
    public function index(int $userId,
                          int $perPage = 15,
                          string $type = 'all',
                          ?string $cursor = null,
                          ?string $dateFrom = null,
                          ?string $dateTo = null):array;
}
