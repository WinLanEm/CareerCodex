<?php

namespace App\Contracts\Repositories\User;

use App\Models\User;

interface UpdateUserRepositoryInterface
{
    public function update(array $data,User $user);
}
