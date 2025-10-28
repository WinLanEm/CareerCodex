<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\Integrations\GetUserIntegrationsRepositoryInterface;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Collection;

class GetUserIntegrationsRepository implements GetUserIntegrationsRepositoryInterface
{
    public function getByUserId(int $userId): Collection
    {
        return Integration::where('user_id', $userId)->get();
    }
}
