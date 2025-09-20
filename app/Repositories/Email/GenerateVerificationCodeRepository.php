<?php

namespace App\Repositories\Email;

use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Models\User;

class GenerateVerificationCodeRepository implements GenerateVerificationCodeRepositoryInterface
{
    public function generate(User $user):string
    {
        $user->verification_code = $this->generateUniqueVerificationCode();

        $user->verification_code_expires_at = now()->addMinutes(60);
        $user->save();
        return $user->verification_code;
    }
    private function generateUniqueVerificationCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (User::where('verification_code', $code)->exists());

        return $code;
    }
}
