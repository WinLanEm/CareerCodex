<?php

namespace App\Repositories\User;

use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Models\User;

class FindUserRepository implements FindUserRepositoryInterface
{
    public function findByEmail($email):?User
    {
        return User::where('email', $email)->first();
    }
}
