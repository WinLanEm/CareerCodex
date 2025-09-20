<?php

namespace App\Http\Controllers\Services\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UrlResource;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class IntegrationRedirectController extends Controller
{
    public function __invoke(ValidateServiceIntegrationRequest $request)
    {
        try {
            $serviceName = $request->get('service');
            $scopes = config("services.{$serviceName}_integration.scopes");

            $redirectResponse = Socialite::driver("{$serviceName}_integration")
                ->stateless()
                ->scopes($scopes)
                ->redirect();

            return new UrlResource($redirectResponse->getTargetUrl());

        } catch (Exception $e) {
            Log::error("Failed to redirect to the $serviceName service",[
                'message' => $e->getMessage(),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return new MessageResource("Failed to redirect to the service. Please try later or same service",false,500);
        }
    }
}
