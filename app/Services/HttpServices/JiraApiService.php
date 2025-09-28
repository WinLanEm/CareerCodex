<?php

namespace App\Services\HttpServices;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Contracts\Services\HttpServices\Jira\JiraRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Jira\JiraWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraApiService implements JiraWorkspaceServiceInterface,JiraProjectServiceInterface, JiraRegisterWebhookInterface
{
    public function __construct(
        private ThrottleServiceInterface $throttleService,
        private UpdateOrCreateWebhookRepositoryInterface $updateOrCreateWebhookRepository,
    )
    {}
    public function getWorkspaces(string $token): array
    {
        return $this->throttleService->for(
            ServiceConnectionsEnum::JIRA,
            function () use($token) {
                $client = Http::withToken($token);
                $providerInstanceUrl = config('services.jira_integration.provider_instance_url');
                $response = $client
                    ->timeout(30)
                    ->get($providerInstanceUrl);
                $response->throw();

                return $response->json();
            },
        );
    }

    public function getProjects(string $token,string $cloudId,PendingRequest $client): array
    {
        $allProjects = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($token, $cloudId, $startAt, $maxResults,$client) {
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
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        CarbonImmutable $updatedSince,
        string $token,
        string $projectKey,
        string $cloudId,
        PendingRequest $client,
        \Closure $closure
    ):void
    {
        $startAt = 0;
        $maxResults = 100;
        $updatedSinceFormatted = $updatedSince->format('Y-m-d H:i');
        $jql = "project = \"{$projectKey}\" AND status = Done AND updated >= \"{$updatedSinceFormatted}\"";

        do {
            $responseJson = $this->throttleService->for(
                ServiceConnectionsEnum::JIRA,
                function () use ($token, $client,$cloudId, $jql, $startAt, $maxResults) {
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
        public function registerWebhook(Integration $integration,PendingRequest $client,string $cloudId,string $siteUrl):array
        {
            return $this->throttleService->for(ServiceConnectionsEnum::JIRA,function () use($integration, $client, $cloudId, $siteUrl) {
                $apiUrl = "https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/webhook";
                $response = $client->get($apiUrl);

                $secret = bin2hex(random_bytes(32));
                $webhookUrl = route('webhook', ['service' => 'jira','secret' => $secret]);

                if ($response->successful()) {
                    $existingHooksOnJira = $response->json('values', []);
                    $baseWebhookUrl = url('api/webhook/jira');
                    foreach ($existingHooksOnJira as $hook) {
                        if (str_starts_with($hook['url'], $baseWebhookUrl . '/')) {
                            return $this->updateOrCreateWebhookRepository->updateOrCreateWebhook([
                                'secret' => basename($hook['url']),
                                'repository' => $siteUrl,
                                'integration_id' => $integration->id,
                                'webhook_id' => $hook['id'],
                                'repository_id' => $cloudId,
                                'events' => $hook['events'],
                                'active' => true,
                            ])->toArray();
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
                Log::info($response->body());
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
