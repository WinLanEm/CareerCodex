<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaProjectRefreshTokenInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaProjectServiceInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Models\Webhook;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AsanaApiService implements AsanaWorkspaceServiceInterface, AsanaProjectServiceInterface, AsanaRegisterWebhookInterface, AsanaProjectRefreshTokenInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
        private UpdateIntegrationInstanceRepositoryInterface $integrationRepository,
    )
    {}

    public function getWorkspaces(string $token,PendingRequest $client): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::ASANA,
            function () use($token,$client) {
                $providerInstanceUrl = config('services.asana_integration.provider_instance_url');
                $response = $client
                    ->withHeaders(['accept' => 'application/json'])
                    ->get($providerInstanceUrl);

                $response->throw();

                return $response->json()['data'];
            }
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $url = config('services.asana_integration.projects_url');
        $params = [
            'workspace' => $cloudId,
            'limit' => 100,
            'opt_fields' => 'name,gid'
        ];
        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::ASANA,
                function () use($client,$url,$params,$token){
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                });

            $projectsOnPage = $responseJson['data'] ?? [];
            $allProjects = array_merge($allProjects, $projectsOnPage);
            $nextPageOffset = $responseJson['next_page']['offset'] ?? null;

        } while ($nextPageOffset);

        return $allProjects;
    }

    public function syncCompletedIssuesForProject(
        string $projectKey,
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        string $projectName,
        string $updatedSince,
        string $token,
        PendingRequest $client,
        \Closure $closure
    )
    {
        $url = config('services.asana_integration.sync_issue');
        $params = [
            'project' => $projectKey,
            'completed_since' => $updatedSince,
            'limit' => 100,
            'opt_fields' => 'name,completed,completed_at,notes,permalink_url'
        ];
        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }

            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::ASANA,
                function () use($client,$url,$params,$token){
                    $response = $client->asJson()->get($url, $params);
                    $response->throw();
                    return $response->json();
                });

            $tasks = $responseJson['data'] ?? [];
            foreach ($tasks as $task) {
                $closure($task);
            }
            $nextPageOffset = $responseJson['next_page']['offset'] ?? null;

        } while ($nextPageOffset);
    }
    public function registerWebhook(Integration $integration,array $project,string $workspaceGid):array
    {
        return $this->throttleService->for(ServiceConnectionsEnum::ASANA,function () use($integration,$project,$workspaceGid){
            $projectGid = $project['gid'];
            $projectName = $project['name'];
            $targetUrl = route('webhook', ['service' => 'asana']);
            $url = config('services.asana_integration.set_webhook_url');

            $getWebhooksUrl = "https://app.asana.com/api/1.0/webhooks?workspace=$workspaceGid";
            $client = Http::withToken($integration->access_token);

            $getWebhooksResponse = $client->get($getWebhooksUrl);

            foreach ($getWebhooksResponse->json('data') as $existingWebhook) {
                $webhook = Webhook::where('webhook_id', $existingWebhook['gid'])->first();
                //$client->delete("https://app.asana.com/api/1.0/webhooks/" . $existingWebhook['gid'])->throw();
                if ($webhook) {
                    $client->delete("https://app.asana.com/api/1.0/webhooks/" . $existingWebhook['gid'])->throw();
                    $webhook->delete();
                }
            }
            $events = [
                [
                    'resource_type' => 'task',
                    'action'        => 'changed',
                    'fields'        => [
                        'completed',
                    ]
                ],
            ];
            $response = $client
                ->asJson()
                ->post($url, [
                    'data' => [
                        'resource' => $projectGid,
                        'target'   => $targetUrl,
                        'filters' => $events
                    ],
                ]);

            $response->throw();
            $newHook = $response->json('data');
            return [
                'integration_id' => $integration->id,
                'repository' => $projectName,
                'repository_id' => $projectGid,
                'webhook_id' => $newHook['gid'],
                'secret' => $response->json('X-Hook-Secret'),
                'events' => $events,
                'active' => $newHook['active'] ?? false,
            ];
        });
    }
    public function refreshAccessToken(Integration $integration):bool
    {
        return $this->throttleService->for(ServiceConnectionsEnum::ASANA,function () use($integration){
            $response = Http::asForm()->post('https://app.asana.com/-/oauth_token', [
                'grant_type'    => 'refresh_token',
                'client_id'     => config('services.asana.client_id'),
                'client_secret' => config('services.asana.client_secret'),
                'refresh_token' => $integration->refresh_token,
            ]);

            $response->throw();

            $data = $response->json();

            $this->integrationRepository->update($integration,[
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at'    => now()->addSeconds($data['expires_in']),
            ]);

            return true;
        });
    }
}
