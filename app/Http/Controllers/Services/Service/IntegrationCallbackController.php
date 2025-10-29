<?php

namespace App\Http\Controllers\Services\Service;

use App\Actions\Github\CheckGitHubAppInstallation;
use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Http\Controllers\Services\AbstractServiceCallbackController;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use App\Models\Integration;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class IntegrationCallbackController extends AbstractServiceCallbackController
{
    public function __construct(
        readonly private UpdateOrCreateIntegrationRepositoryInterface $connectionRepository,
        readonly private GetIntegrationInstanceStrategyInterface      $getProviderInstanceStrategy,
        readonly private CheckGitHubAppInstallation $checkGitHubAppInstallation,
    )
    {
    }

    public function __invoke(ValidateServiceIntegrationRequest $request,string $stringService)
    {
        try {
            if($request->get('setup_action')){
                $redirectUrl = config('services.frontend.services_redirect');
                return redirect()->away($redirectUrl);
            }
            $validationResult = $this->validateState($request->input('state'), $stringService);
            if (isset($validationResult['error'])) {
                return new MessageResource($validationResult['error'], false, 401);
            }

            $serviceEnum = ServiceConnectionsEnum::tryFrom($stringService);
            if (!$serviceEnum) {
                return new MessageResource('Service not supported',false,404);
            }

            $issueToken = $validationResult['data']['issue_token'] ?? false;

            $providerUser = Socialite::driver($stringService . "_integration")->stateless()->user();

            if ($serviceEnum === ServiceConnectionsEnum::GITHUB) {
                $checkResult = ($this->checkGitHubAppInstallation)($providerUser);
                if ($checkResult) {
                    if ($issueToken) {
                        return $checkResult;
                    } else {
                        $urlToRedirect = $checkResult->resource;
                        return redirect()->away($urlToRedirect);
                    }
                }
            }

            $integration = $this->connectionRepository->updateOrCreate($serviceEnum,$providerUser);

            if(!$integration){
                return new MessageResource("$stringService email not equal to your email",false,401);
            }

            $this->getProviderInstanceStrategy->getInstance($integration);
            if ($issueToken) {
                return new MessageResource("Provider successfully connected", true, 201);
            } else {
                $redirectUrl = config('services.frontend.services_redirect');
                return redirect()->away($redirectUrl);
            }
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
