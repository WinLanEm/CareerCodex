<?php

namespace App\Services\Webhook\Handlers;

use App\Enums\ServiceConnectionsEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GithubWebhookHandler extends AbstractWebhookHandler
{
    public function verify(array $payload, string $rawPayload,array $headers,?string $secret): bool
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
                        ->where('service', ServiceConnectionsEnum::GITHUB->value);
                });
        });

        if (!$webhook || !$webhook->secret) {
            Log::warning('Webhook secret not found for repository', ['repo' => $repoName]);
            return false; // В проде лучше true, чтобы не блокировать другие хуки
        }
        $expectedSignature = 'sha256=' . hash_hmac('sha256',$rawPayload, $webhook->secret);
        return hash_equals($expectedSignature, $signature);
    }

    public function handle(array $payload, array $headers): void
    {
        $eventType = $headers['x-github-event'][0] ?? null;

        match ($eventType) {
            'push' => $this->handlePush($payload),
            'pull_request' => $this->handlePullRequest($payload),
            'ping' => null,
            default => Log::info('Unsupported GitHub event type: ' . $eventType),
        };
    }

    private function handlePush(array $payload): void
    {
        $repoName = $payload['repository']['full_name'];

        $integration = $this->findIntegrationById($payload['sender']['id'],ServiceConnectionsEnum::GITHUB);

        if (!$integration) return;

        foreach ($payload['commits'] as $commit) {
            if ($commit['distinct']) {
                $this->activityRepository->updateOrCreateDeveloperActivity([
                    'integration_id' => $integration->id,
                    'type' => 'commit',
                    'external_id' => $commit['id'],
                    'repository_name' => $repoName,
                    'title' => mb_substr($commit['message'], 0, 255),
                    'url' => $commit['url'],
                    'completed_at' => CarbonImmutable::parse($commit['timestamp']),
                    'is_from_provider' => true,
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
        $integration = $this->findIntegrationById($pr['user']['id'],ServiceConnectionsEnum::GITHUB);
        if (!$integration) return;

        $this->activityRepository->updateOrCreateDeveloperActivity([
            'integration_id' => $integration->id,
            'type' => 'pull_request',
            'external_id' => $pr['number'],
            'repository_name' => $payload['repository']['full_name'],
            'title' => mb_substr($pr['title'], 0, 255),
            'url' => $pr['html_url'],
            'is_from_provider' => true,
            'completed_at' => CarbonImmutable::parse($pr['merged_at']),
            'additions' => $pr['additions'] ?? 0,
            'deletions' => $pr['deletions'] ?? 0,
        ]);
    }
}
