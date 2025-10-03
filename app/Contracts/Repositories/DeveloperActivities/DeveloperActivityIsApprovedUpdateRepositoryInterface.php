<?php

namespace App\Contracts\Repositories\DeveloperActivities;

interface DeveloperActivityIsApprovedUpdateRepositoryInterface
{
    public function update(array $developerActivityIds): bool;
}
