<?php

namespace App\Repositories\Integrations;

use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User;

class UpdateOrCreateIntegrationRepository implements UpdateOrCreateIntegrationRepositoryInterface
{
    public function __construct(
        private FindUserRepositoryInterface $findUserRepository,
    )
    {
    }

    public function updateOrCreate(ServiceConnectionsEnum $service, User $socialiteUser):?Integration
    {
        $user = $this->findUserRepository->findByEmail($socialiteUser->getEmail());

        if(!$user){
            return null;
        }

        $expiresAtTimestamp = null;

        if ($socialiteUser->expiresIn) {
            $expiresAtTimestamp = now()->addSeconds($socialiteUser->expiresIn);
        }
        return Integration::updateOrCreate(
            [
                'user_id' => $user->id,
                'service' => $service->value,
                'service_user_id' => $socialiteUser->getId(),
            ],
            [
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'expires_at' => $expiresAtTimestamp,
                'next_check_provider_instances_at' => now()->addHour()
            ]
        );
    }
}
