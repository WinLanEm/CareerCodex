<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseApiService
{
    public function __construct(
        protected ThrottleServiceInterface $throttleService,
        protected UpdateIntegrationRepositoryInterface $integrationRepository,
    ) {}

    abstract protected function getServiceEnum(): ServiceConnectionsEnum;

    public function refreshAccessToken(Integration $integration): bool
    {
        return $this->throttleService->for($this->getServiceEnum(), function () use ($integration) {
            $serviceKey = $this->getServiceEnum()->value . '_integration';
            $url = config("services.{$serviceKey}.get_access_token_url");

            $params = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $integration->refresh_token,
                'client_id'     => config("services.{$serviceKey}.client_id"),
                'client_secret' => config("services.{$serviceKey}.client_secret"),
            ];

            $response = Http::asForm()->post($url, $params);

            try {
                $response->throw();
            } catch (\Exception $e) {
                Log::error('Refresh token failed', [
                    'service' => $serviceKey,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);
                return false;
            }
            $data = $response->json();

            if (!isset($data['access_token'])) {
                Log::warning('Refresh token response did not contain a new access_token.', [
                    'service' => $serviceKey,
                    'integration_id' => $integration->id,
                    'response' => print_r($data, true),
                ]);
                return false;
            }

            $updateData = [
                'access_token' => $data['access_token'],
                'expires_at'   => now()->addSeconds($data['expires_in']),
            ];

            if (isset($data['refresh_token'])) {
                $updateData['refresh_token'] = $data['refresh_token'];
            }

            $this->integrationRepository->update($integration, $updateData);
            return true;
        });
    }

    protected function getHttpClient(Integration $integration): PendingRequest
    {
        if ($integration->expires_at?->isPast()) {
            $isRefreshed = $this->refreshAccessToken($integration);

            if (!$isRefreshed) {
                throw new \Exception("Token for integration ID {$integration->id} is expired and could not be refreshed. User re-authentication is required.");
            }

            $integration->refresh();
        }

        return Http::withToken($integration->access_token);
    }
}
