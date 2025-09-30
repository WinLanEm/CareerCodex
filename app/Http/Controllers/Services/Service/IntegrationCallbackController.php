<?php

namespace App\Http\Controllers\Services\Service;

use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Services\HttpServices\Github\GithubCheckIfAppInstalledInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UrlResource;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class IntegrationCallbackController extends Controller
{
    public function __construct(
        private UpdateOrCreateIntegrationRepositoryInterface $connectionRepository,
        private GetIntegrationInstanceStrategyInterface      $getProviderInstanceStrategy,
        private GithubCheckIfAppInstalledInterface $appInstalled,
    )
    {
    }

    public function __invoke(ValidateServiceIntegrationRequest $request,string $stringService)
    {
        try {
            $serviceEnum = ServiceConnectionsEnum::tryFrom($stringService);
            if (!$serviceEnum) {
                return new MessageResource('Service not supported',false,404);
            }
            $providerUser = Socialite::driver($stringService . "_integration")->stateless()->user();

            $integration = $this->connectionRepository->updateOrCreate($serviceEnum,$providerUser);
            if(!$integration){
                return new MessageResource("$stringService email not equal to your email",false,401);
            }
            if ($serviceEnum === ServiceConnectionsEnum::GITHUB) {
                $isInstalled = $this->appInstalled->checkIfAppIsInstalled($providerUser);

                if (!$isInstalled) {
                    $appSlug = config('services.github_integration.app_slug');
                    $installationUrl = "https://github.com/apps/{$appSlug}/installations/new";

                    return new UrlResource($installationUrl,false,403);
                }
            }
            $this->getProviderInstanceStrategy->getInstance($integration);
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
