<?php

namespace App\Contracts\Repositories\User;

use App\Enums\AuthServiceEnum;
use App\Models\User as AppUser;
use Laravel\Socialite\Contracts\User;

interface UpdateOrCreateUserRepositoryInterface
{
    public function updateOrCreateProviderUser(User $providerUser,AuthServiceEnum $serviceEnum):?AppUser;
}
