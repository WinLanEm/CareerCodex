<?php

namespace App\Contracts\Services\HttpServices\Github;

use Laravel\Socialite\Contracts\User;

interface GithubCheckIfAppInstalledInterface
{
    public function checkIfAppIsInstalled(User $user):bool;
}
