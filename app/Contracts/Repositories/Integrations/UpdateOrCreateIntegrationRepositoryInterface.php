<?php

namespace App\Contracts\Repositories\Integrations;

use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Laravel\Socialite\Contracts\User;

interface UpdateOrCreateIntegrationRepositoryInterface
{
    public function updateOrCreate(ServiceConnectionsEnum $service,User $socialiteUser):?Integration;
}
