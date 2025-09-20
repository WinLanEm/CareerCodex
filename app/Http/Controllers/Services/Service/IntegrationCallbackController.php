<?php

namespace App\Http\Controllers\Services\Service;

use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class IntegrationCallbackController extends Controller
{
    public function __construct(
        private UpdateOrCreateIntegrationRepositoryInterface $connectionRepository,
        private GetIntegrationInstanceStrategyInterface      $getProviderInstanceStrategy,
    )
    {
    }

    public function __invoke(ValidateServiceIntegrationRequest $request)
    {
        try {
            $stringService = $request->get('service');
            $serviceEnum = ServiceConnectionsEnum::tryFrom($stringService);
            $providerUser = Socialite::driver($stringService . "_integration")->stateless()->user();
            $serviceConnection = $this->connectionRepository->updateOrCreate($serviceEnum,$providerUser);
            if(!$serviceConnection){
                return new MessageResource("$stringService email not equal to your email",false,401);
            }
            $this->getProviderInstanceStrategy->getInstance($serviceConnection,true);
            return new MessageResource("provider successful updated",true,201);
        }catch (Exception $exception){
            Log::error("An error occurred during authentication with the $stringService.",[
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'trace' => $exception->getTraceAsString(),
            ]);
            return new MessageResource('An error occurred during authentication with the service.', false, 500);
        }
    }
}
