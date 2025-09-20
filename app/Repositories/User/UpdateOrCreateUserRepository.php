<?php

namespace App\Repositories\User;

use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Enums\AuthServiceEnum;
use App\Models\User as AppUser;
use Laravel\Socialite\Contracts\User;

class UpdateOrCreateUserRepository implements UpdateOrCreateUserRepositoryInterface
{
    public function __construct(
        private FindUserRepositoryInterface $findUserRepository,
    )
    {
    }

    public function updateOrCreateProviderUser(User $providerUser, AuthServiceEnum $serviceEnum): ?AppUser
    {
        $user = $this->findUserRepository->findByEmail($providerUser->getEmail());

        if($user && $user->password){
            $user->update([
                "provider_id" => $providerUser->getId(),
                'provider' => $serviceEnum->value,
            ]);
            return $user->fresh();
        }

        if($user && !$user->password){
            $user->update([
                "provider_id" => $providerUser->getId(),
                'provider' => $serviceEnum->value,
                'name' => $providerUser->getName() ?? $providerUser->getNickname(),
            ]);
            return $user->fresh();
        }

        return AppUser::create([
            "email" => $providerUser->getEmail(),
            'name' => $providerUser->getNickname() ?? $providerUser->getName(),
            'email_verified_at' => now(),
            "provider_id" => $providerUser->getId(),
            'provider' => $serviceEnum->value,
            'provider_token' => $providerUser->token,
        ]);
    }
}
