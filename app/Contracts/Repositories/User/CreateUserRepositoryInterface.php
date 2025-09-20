<?php

namespace App\Contracts\Repositories\User;

use App\Models\User;

interface CreateUserRepositoryInterface
{
    public function create(string $email, string $password, string $name):?User;
}
