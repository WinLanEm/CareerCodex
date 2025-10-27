<?php

namespace App\Http\Controllers\Services\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateOAuthProviderRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UrlResource;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialRedirectController extends Controller
{
    public function __invoke(ValidateOAuthProviderRequest $request)
    {
        try{
            $link = Socialite::driver($request->get('provider'))->stateless()->redirect();
            return new UrlResource($link->getTargetUrl());
        }catch (Exception $exception){
            Log::error("Failed to redirect to the" . "$request->get('provider').",[
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'exception' => $exception,
            ]);
            return new MessageResource("Failed to redirect to the provider",false,500);
        }
    }
}
