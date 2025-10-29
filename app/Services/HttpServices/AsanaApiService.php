<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaGetTaskInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaProjectServiceInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;

class AsanaApiService extends BaseApiService implements AsanaWorkspaceServiceInterface, AsanaProjectServiceInterface, AsanaRegisterWebhookInterface, AsanaGetTaskInterface
{
    public function __construct(
        ThrottleServiceInterface                 $throttleService,
        UpdateIntegrationRepositoryInterface     $integrationRepository,
        readonly private EloquentWebhookRepositoryInterface $webhookRepository,
    )
    {
        parent::__construct($throttleService, $integrationRepository);
    }

    protected function getServiceEnum(): ServiceConnectionsEnum
    {
        return ServiceConnectionsEnum::ASANA;
    }

    public function getWorkspaces(Integration $integration): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::ASANA,
            function () use($integration) {
                $providerInstanceUrl = config('services.asana_integration.provider_instance_url');
                $client = $this->getHttpClient($integration);
                $response = $client
                    ->withHeaders(['accept' => 'application/json'])
                    ->get($providerInstanceUrl);

                $response->throw();

                return $response->json()['data'];
            }
        );
    }

    public function getProjects(Integration $integration,string $cloudId): array
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
                function () use($url,$params,$integration){
                    $client = $this->getHttpClient($integration);
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
        string                                       $projectKey,
        AchievementUpdateOrCreateRepositoryInterface $repository,
        string                                       $projectName,
        Integration $integration,
        \Closure                                     $closure
    )
    {
        $updatedSince = now()->subDays(7)->toIso8601String();
        $client = $this->getHttpClient($integration);
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
                function () use($client,$url,$params){
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

            $events = [
                [
                    'resource_type' => 'task',
                    'action'        => 'changed',
                    'fields'        => [
                        'completed',
                    ]
                ],
            ];

            $getWebhooksUrl = config('services.asana_integration.get_webhooks_url');
            $getWebhooksUrl = str_replace('{workspaceGid}', $workspaceGid, $getWebhooksUrl);
            $client = $this->getHttpClient($integration);

            $getWebhooksResponse = $client->get($getWebhooksUrl);
            foreach ($getWebhooksResponse->json('data') as $existingWebhook) {
                $webhook = $this->webhookRepository->find(
                    function (Builder $query) use($existingWebhook) {
                        return $query->where('webhook_id', $existingWebhook['gid']);
                    });

                if ($webhook) {
                    return $webhook->toArray();
                }

                $client->delete("$url/" . $existingWebhook['gid'])->throw();
                break;
            }

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
    public function getTask(Integration $integration, string $taskGid): Response
    {
        return $this->throttleService->for($this->getServiceEnum(), function () use ($integration, $taskGid) {
            $url = config('services.asana_integration.sync_issue');

            $client = $this->getHttpClient($integration);

            $response = $client->get("$url/$taskGid");

            $response->throw();

            return $response;
        });
    }
}
