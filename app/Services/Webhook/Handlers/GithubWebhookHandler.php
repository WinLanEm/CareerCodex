<?php

namespace App\Services\Webhook\Handlers;

use App\Enums\ServiceConnectionsEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GithubWebhookHandler extends AbstractWebhookHandler
{
    public function verify(array $payload, array $headers): bool
    {
        $signature = $headers['x-hub-signature-256'][0] ?? null;
        if (!$signature) {
            return false;
        }

        $repoName = $payload['repository']['full_name'] ?? null;
        if (!$repoName) return false;

        $webhook = $this->webhookRepository->find(function (Builder $query) use ($payload, $repoName) {
            return $query->where('repository', $repoName)
                ->where('integration_id', function ($subQuery) use ($payload) {
                    $subQuery->select('id')->from('integrations')
                        ->where('external_user_id', $payload['sender']['id'])
                        ->where('service', ServiceConnectionsEnum::GITHUB->value);
                });
        });

        if (!$webhook || !$webhook->secret) {
            Log::warning('Webhook secret not found for repository', ['repo' => $repoName]);
            return false; // В проде лучше true, чтобы не блокировать другие хуки
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $webhook->secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function handle(array $payload, array $headers): void
    {
        $eventType = $headers['x-github-event'][0] ?? null;

        match ($eventType) {
            'push' => $this->handlePush($payload),
            'pull_request' => $this->handlePullRequest($payload),
            default => Log::info('Unsupported GitHub event type: ' . $eventType),
        };
    }

    private function handlePush(array $payload): void
    {
        $repoName = $payload['repository']['full_name'];
        $integrationId = $this->findIntegrationId($payload['sender']['id'],ServiceConnectionsEnum::GITHUB);

        if (!$integrationId) return;

        foreach ($payload['commits'] as $commit) {
            if ($commit['distinct']) {
                $this->activityRepository->updateOrCreateDeveloperActivity([
                    'integration_id' => $integrationId,
                    'type' => 'commit',
                    'external_id' => $commit['id'],
                    'repository_name' => $repoName,
                    'title' => mb_substr($commit['message'], 0, 255),
                    'url' => $commit['url'],
                    'completed_at' => CarbonImmutable::parse($commit['timestamp']),
                    // Additions/deletions в push-событии для коммита недоступны напрямую,
                    // для этого нужен отдельный API-запрос, который можно вынести в другую джобу
                    'additions' => 0,
                    'deletions' => 0,
                ]);
            }
        }
    }

    private function handlePullRequest(array $payload): void
    {
        if ($payload['action'] !== 'closed' || !$payload['pull_request']['merged']) {
            return;
        }

        $pr = $payload['pull_request'];
        $integrationId = $this->findIntegrationId($pr['user']['id'],ServiceConnectionsEnum::GITHUB);
        if (!$integrationId) return;

        $this->activityRepository->updateOrCreateDeveloperActivity([
            'integration_id' => $integrationId,
            'type' => 'pull_request',
            'external_id' => $pr['number'],
            'repository_name' => $payload['repository']['full_name'],
            'title' => mb_substr($pr['title'], 0, 255),
            'url' => $pr['html_url'],
            'completed_at' => CarbonImmutable::parse($pr['merged_at']),
            'additions' => $pr['additions'] ?? 0,
            'deletions' => $pr['deletions'] ?? 0,
        ]);
    }
}
