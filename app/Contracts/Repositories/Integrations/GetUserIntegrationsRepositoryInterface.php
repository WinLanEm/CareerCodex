<?php

namespace App\Contracts\Repositories\Integrations;

use Illuminate\Database\Eloquent\Collection;

interface GetUserIntegrationsRepositoryInterface
{
    public function getByUserId(int $userId): Collection;
}
