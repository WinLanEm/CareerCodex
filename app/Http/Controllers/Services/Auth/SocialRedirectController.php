<?php

namespace App\Http\Controllers\Services\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateOAuthProviderRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UrlResource;
use App\Traits\CreateSignedState;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialRedirectController extends Controller
{
    use CreateSignedState;
    public function __invoke(ValidateOAuthProviderRequest $request)
    {
        try{
            $signedState = $this->createSignedState([
                'issue_token' => $request->boolean('issue_token', false),
                'timestamp' => now()->timestamp
            ]);

            $link = Socialite::driver($request->get('provider'))
                ->stateless()
                ->with(['state' => $signedState])
                ->redirect();
            return new UrlResource($link->getTargetUrl());
        }catch (Exception $exception){
            Log::error("Failed to redirect to the" . $request->get('provider'),[
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'exception' => $exception,
            ]);
            return new MessageResource("Failed to redirect to the provider",false,500);
        }
    }
}
