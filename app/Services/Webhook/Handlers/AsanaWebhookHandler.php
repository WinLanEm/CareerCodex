<?php

namespace App\Services\Webhook\Handlers;

use App\Contracts\Services\HttpServices\Asana\AsanaProjectRefreshTokenInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsanaWebhookHandler extends AbstractWebhookHandler
{
    public function verify(array $payload, string $rawPayload, array $headers, ?string $secret): bool
    {
        $signature = $headers['x-hook-signature'][0] ?? null;

        if ($signature && empty($payload['events'])) {
            return true;
        }

        if (!$signature) {
            return false;
        }

        $integration = $this->findIntegrationById($payload['events'][0]['user']['gid'],ServiceConnectionsEnum::ASANA);
        $extendedTaskData = config('services.asana_integration.sync_issue');
        $taskGid = $payload['events'][0]['resource']['gid'];
        $this->updateAccessToken($integration);
        $res = Http::withToken($integration->access_token)->get("$extendedTaskData/$taskGid")->throw();

        $projectGid = $res->json('data')['projects'][0]['gid'];
        $webhook = $this->webhookRepository->find(function (Builder $query) use ($projectGid) {
            return $query->where('repository_id', $projectGid);
        });

        if (!$webhook || !$webhook->secret) {
            Log::warning('Webhook secret not found for project.', ['project_gid' => $projectGid]);
            return false;
        }

        $computedSignature = hash_hmac('sha256', $rawPayload, $webhook->secret);

        return hash_equals($computedSignature, $signature);
    }

    public function handle(array $payload, array $headers): void
    {
        foreach ($payload['events'] as $event) {
            $action = $event['action'] ?? null;
            if (!in_array($action, ['added', 'changed'])) {
                continue;
            }

            $userGid = $event['user']['gid'] ?? null;
            if (!$userGid) continue;

            $integration = $this->findIntegrationById($userGid, ServiceConnectionsEnum::ASANA);
            if (!$integration) continue;

            $taskGid = $event['resource']['gid'] ?? null;
            if (!$taskGid) continue;

            $extendedTaskDataUrl = config('services.asana_integration.sync_issue');
            $this->updateAccessToken($integration);
            $res = Http::withToken($integration->access_token)->get("$extendedTaskDataUrl/$taskGid");

            if (!$res->successful()) continue;

            $data = $res->json('data');

            if ($data['completed']) {
                $isFollower = false;
                foreach ($data['followers'] ?? [] as $follower) {
                    if (($follower['gid'] ?? null) === $integration->service_user_id) {
                        $isFollower = true;
                        break;
                    }
                }

                if ($isFollower) {
                    $projectGid = $data['projects'][0]['gid'] ?? null;
                    if (!$projectGid) continue;

                    $integrationInstance = $this->integrationInstanceByClosureRepository->findIntegrationInstanceByClosure(function (Builder $query) use ($projectGid) {
                        return $query->where('external_id', $projectGid);
                    });

                    if (!$integrationInstance) continue;

                    $this->achievementRepository->updateOrCreate([
                        'title' => $data['name'] ?? 'Untitled Task',
                        'link' => $data['permalink_url'] ?? null,
                        'description' => $data['notes'] ?? null,
                        'result' => null,
                        'hours_spent' => null,
                        'date' => $data['completed_at'] ?? now(),
                        'is_approved' => false,
                        'is_from_provider' => true,
                        'integration_instance_id' => $integrationInstance->id,
                        'project_name' => $data['projects'][0]['name'] ?? 'Unknown Project',
                    ], null);
                }
            }
        }
    }
    private function updateAccessToken(Integration $integration):void
    {
        if ($integration->expires_at->isPast()) {
            $oauthService = app(AsanaProjectRefreshTokenInterface::class);
            $isRefreshed = $oauthService->refreshAccessToken($integration);

            if (!$isRefreshed) {
                Log::error('Could not continue processing webhook due to failed token refresh.');
            }
            $integration->refresh();
        }
    }
}
