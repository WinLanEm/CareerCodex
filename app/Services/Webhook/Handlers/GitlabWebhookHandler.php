<?php

namespace App\Services\Webhook\Handlers;

use App\Enums\ServiceConnectionsEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GitlabWebhookHandler extends AbstractWebhookHandler
{
    public function verify(array $payload, array $headers): bool
    {
        $token = $headers['x-gitlab-token'][0] ?? null;
        if (!$token) {
            return false;
        }

        $projectId = $payload['project']['id'] ?? null;
        if (!$projectId) {
            return false;
        }

        $webhook = $this->webhookRepository->find(
            function (Builder $query) use($projectId) {
                return $query->where('webhook_id', $projectId)
                    ->whereHas('integration', function ($subQuery) {
                        $subQuery->where('service', ServiceConnectionsEnum::GITLAB->value);
                    });
            }
        );

        if (!$webhook || !$webhook->secret) {
            Log::warning('Webhook secret not found for GitLab project', ['project_id' => $projectId]);
            return false;
        }

        return hash_equals($webhook->secret, $token);
    }

    public function handle(array $payload, array $headers): void
    {
        $eventType = $payload['object_kind'] ?? null;

        match ($eventType) {
            'push' => $this->handlePush($payload),
            'merge_request' => $this->handleMergeRequest($payload),
            default => Log::info('Unsupported GitLab event type: ' . $eventType),
        };
    }

    private function handlePush(array $payload): void
    {
        $repoName = $payload['project']['path_with_namespace'];
        $integrationId = $this->findIntegrationId($payload['user_id'],ServiceConnectionsEnum::GITLAB);

        if (!$integrationId) return;

        foreach ($payload['commits'] as $commit) {
            $this->activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $integrationId,
                'type' => 'commit',
                'external_id' => $commit['id'],
                'repository_name' => $repoName,
                'title' => mb_substr($commit['message'], 0, 255),
                'url' => $commit['url'],
                'completed_at' => CarbonImmutable::parse($commit['timestamp']),
                'additions' => $commit['added'] ? count($commit['added']) : 0,
                'deletions' => $commit['removed'] ? count($commit['removed']) : 0,
            ]);
        }
    }

    private function handleMergeRequest(array $payload): void
    {
        $mr = $payload['object_attributes'];

        if ($mr['state'] !== 'merged') {
            return;
        }

        $integrationId = $this->findIntegrationId($mr['author_id'],ServiceConnectionsEnum::GITLAB);
        if (!$integrationId) return;

        $this->activityRepository->updateOrCreateDeveloperActivity([
            'integration_id' => $integrationId,
            'type' => 'pull_request',
            'external_id' => $mr['iid'],
            'repository_name' => $payload['project']['path_with_namespace'],
            'title' => mb_substr($mr['title'], 0, 255),
            'url' => $mr['url'],
            'completed_at' => CarbonImmutable::parse($mr['updated_at']),
            // GitLab не предоставляет additions/deletions в вебхуке MR.
            // Нужно делать доп. запрос к API или оставить 0.
            'additions' => 0,
            'deletions' => 0,
        ]);
    }
}
