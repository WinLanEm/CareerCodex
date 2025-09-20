<?php

namespace App\Contracts\Repositories\User;

use App\Models\User;

interface FindUserRepositoryInterface
{
    public function findByEmail($email):?User;
}
