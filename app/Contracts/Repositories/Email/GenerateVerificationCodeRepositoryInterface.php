<?php

namespace App\Contracts\Repositories\Email;

use App\Models\User;

interface GenerateVerificationCodeRepositoryInterface
{
    public function generate(User $user):string;
}
