<?php

namespace App\Http\Controllers\Services\Auth;

use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Enums\AuthServiceEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateOAuthProviderRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UrlResource;
use App\Http\Resources\User\AuthResource;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private UpdateOrCreateUserRepositoryInterface $updateOrCreateUserRepository,
    )
    {}

    public function __invoke(ValidateOAuthProviderRequest $request)
    {
        try{
            $provider = AuthServiceEnum::tryFrom($request->get('provider'));
            $providerUser = Socialite::driver($provider->value)->stateless()->user();
            $user = $this->updateOrCreateUserRepository->updateOrCreateProviderUser($providerUser,$provider);
            $token = $user->createToken("$provider->value-token")->plainTextToken;
            return new AuthResource($user,'success',$token);
        }catch (Exception $exception){
            Log::error("An error occurred during authentication with the $provider->value",[
                'exception' => $exception,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return new MessageResource("An error occurred during authentication with the service.",false,500);
        }
    }
}


