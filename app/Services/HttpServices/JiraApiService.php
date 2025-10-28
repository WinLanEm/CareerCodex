<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Contracts\Services\HttpServices\Jira\JiraRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Jira\JiraWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraApiService implements JiraWorkspaceServiceInterface,JiraProjectServiceInterface, JiraRegisterWebhookInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
    )
    {}
    public function getWorkspaces(Integration $integration): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::JIRA,
            function () use($integration) {
                $client = Http::withToken($integration->access_token);
                $providerInstanceUrl = config('services.jira_integration.provider_instance_url');
                $response = $client
                    ->timeout(30)
                    ->get($providerInstanceUrl);
                $response->throw();

                return $response->json();
            },
        );
    }

    public function getProjects(Integration $integration,string $cloudId): array
    {
        $allProjects = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($integration, $cloudId, $startAt, $maxResults) {
                    $client = Http::withToken($integration->access_token);
                    $url = config('services.jira_integration.projects_url');
                    $url = str_replace('{cloudId}', $cloudId, $url);
                    $response = $client->asJson()->timeout(30)->get($url, [
                        'startAt' => $startAt,
                        'maxResults' => $maxResults,
                    ]);
                    $response->throw();
                    return $response->json();
                },
            );

            $projects = $responseJson['values'] ?? [];
            $allProjects = array_merge($allProjects, $projects);

            $startAt += count($projects);
            $isLast = $responseJson['isLast'] ?? true;

        } while (!$isLast);

        return $allProjects;
    }

    public function syncCompletedIssuesForProject(
        AchievementUpdateOrCreateRepositoryInterface $repository,
        Integration                                  $integration,
        string                                       $projectKey,
        string                                       $cloudId,
        \Closure                                     $closure
    ):void
    {
        $startAt = 0;
        $maxResults = 100;
        $updatedSinceFormatted = now()->subDays(7)->format('Y-m-d H:i');
        $jql = "project = {$projectKey} AND status = Done AND updated >= '{$updatedSinceFormatted}'";

        do {
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($integration,$cloudId, $jql, $startAt, $maxResults) {
                    $client = Http::withToken($integration->access_token);
                    $url = config('services.jira_integration.sync_issue');
                    $url = str_replace('{cloudId}', $cloudId, $url);
                    $response = $client->asJson()->timeout(30)->get($url, [
                        'jql' => $jql,
                        'fields' => 'summary,resolutiondate,description,project,issuetype,status',
                        'startAt' => $startAt,
                        'maxResults' => $maxResults
                    ]);

                    $response->throw();
                    return $response->json();
                },
            );

            $issues = $responseJson['issues'] ?? [];
            foreach ($issues as $issue) {
                $closure($issue);
            }

            $startAt += count($issues);

        } while ($startAt < ($responseJson['total'] ?? 0));
    }
        public function registerWebhook(Integration $integration,string $cloudId,string $siteUrl):array
        {
            return $this->throttleService->for(ServiceConnectionsEnum::JIRA,function () use($integration, $cloudId, $siteUrl) {
                $client = Http::withToken($integration->access_token);
                $apiUrl = config('services.jira_integration.register_webhook_url');
                $apiUrl = str_replace('{cloudId}', $cloudId, $apiUrl);
                $response = $client->get($apiUrl);
                $secret = bin2hex(random_bytes(32));
                $webhookUrl = route('webhook', ['service' => 'jira']) . '?secret=' . $secret;
                if ($response->successful()) {
                    $existingHooksOnJira = $response->json('values', []);
                    $baseWebhookUrl = url('api/webhook/jira') . '?secret=';
                    foreach ($existingHooksOnJira as $hook) {
                        if (str_starts_with($hook['url'], $baseWebhookUrl)) {
                            $webhook = Webhook::where('webhook_id',$hook['id'])->first();
                            if($webhook){
                                return $webhook->toArray();
                            }

                            $existingSecret = '';
                            if (isset($hook['url'])) {
                                $queryString = parse_url($hook['url'], PHP_URL_QUERY);
                                parse_str($queryString, $queryParams);
                                $existingSecret = $queryParams['secret'] ?? '';
                            }

                            return [
                                'integration_id' => $integration->id,
                                'repository' => $siteUrl,
                                'repository_id' => $cloudId,
                                'webhook_id' => $hook['id'],
                                'secret' => $existingSecret ?? $secret,
                                'events' => $hook['events'] ?? ['jira:issue_created', 'jira:issue_updated'],
                                'active' => true,
                            ];
                        }
                    }
                }

                $payload = [
                    'url' => $webhookUrl,
                    'webhooks' => [
                        [
                            'jqlFilter' => 'status = "Done"',
                            'events' => [
                                'jira:issue_created',
                                'jira:issue_updated',
                            ],
                        ],
                    ],
                ];

                $response = $client->post($apiUrl, $payload);

                if (!$response->successful()) {
                    Log::error('Failed to register Jira webhook. API Error.', [
                        'cloud_id' => $cloudId,
                        'status_code' => $response->status(),
                        'response_body' => $response->body(),
                    ]);
                    return [];
                }

                $webhookData = $response->json();

                if(!isset($webhookData['webhookRegistrationResult'][0]['createdWebhookId'])) {
                    return [];
                }
                return [
                    'integration_id' => $integration->id,
                    'repository' => $siteUrl,
                    'repository_id' => $cloudId,
                    'webhook_id' => $webhookData['webhookRegistrationResult'][0]['createdWebhookId'],
                    'secret' => $secret,
                    'events' => $payload['webhooks'][0]['events'],
                    'active' => true,
                ];
            });
        }
}
