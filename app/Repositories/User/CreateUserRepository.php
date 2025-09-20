<?php

namespace App\Repositories\User;

use App\Contracts\Repositories\User\CreateUserRepositoryInterface;
use App\Models\User;

class CreateUserRepository implements CreateUserRepositoryInterface
{
    public function create(string $email, string $password, string $name): ?User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }
}
