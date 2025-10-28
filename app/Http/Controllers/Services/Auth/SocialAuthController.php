<?php

namespace App\Http\Controllers\Services\Auth;

use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Enums\AuthServiceEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\AbstractServiceCallbackController;
use App\Http\Requests\Services\ValidateOAuthProviderRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\User\AuthResource;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends AbstractServiceCallbackController
{
    public function __construct(
        private UpdateOrCreateUserRepositoryInterface $updateOrCreateUserRepository,
    )
    {}

    public function __invoke(ValidateOAuthProviderRequest $request)
    {
        try{
            $provider = AuthServiceEnum::tryFrom($request->get('provider'));
            $validationResult = $this->validateState($request->input('state'), $provider->value);
            if (isset($validationResult['error'])) {
                return new MessageResource($validationResult['error'], false, 401);
            }
            $providerUser = Socialite::driver($provider->value)->stateless()->user();
            $user = $this->updateOrCreateUserRepository->updateOrCreateProviderUser($providerUser,$provider);
            if ($validationResult['data']['issue_token']) {
                $token = $user->createToken("$provider->value-token")->plainTextToken;
                return new AuthResource($user, 'success', $token);
            } else {
                $redirectUrl = config('services.frontend.url');
                Auth::guard('web')->login($user,true);
                $request->session()->regenerate();
                return redirect()->away($redirectUrl);
            }
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


