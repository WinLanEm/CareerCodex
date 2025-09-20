<?php

namespace App\Repositories\User;

use App\Contracts\Repositories\User\UpdateUserRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdateUserRepository implements UpdateUserRepositoryInterface
{
    public function update(array $data, Authenticatable $user)
    {
        $user->update($data);
        return $user->fresh();
    }
}
