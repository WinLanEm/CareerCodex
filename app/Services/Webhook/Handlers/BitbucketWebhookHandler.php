<?php

namespace App\Services\Webhook\Handlers;

use App\Enums\ServiceConnectionsEnum;
use App\Models\Webhook;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isNull;

class BitbucketWebhookHandler extends AbstractWebhookHandler
{

    public function verify(array $payload, string $rawPayload,array $headers,?string $secret): bool
    {
        $signature = $headers['x-hub-signature'][0] ?? null;
        if (!$signature) {
            Log::warning('Bitbucket webhook signature missing.');
            return false;
        }

        $hookUuid = $headers['x-hook-uuid'][0] ?? null;
        if (!$hookUuid) {
            Log::warning('Bitbucket webhook UUID header missing.');
            return false;
        }

        $hookUuidWithBraces = '{' . trim($hookUuid, '{}') . '}';

        $webhook = $this->webhookRepository->find(
            function(Builder $query) use ($hookUuidWithBraces) {
                return $query->where('webhook_id', $hookUuidWithBraces)
                    ->whereHas('integration', function ($subQuery) {
                        $subQuery->where('service', ServiceConnectionsEnum::BITBUCKET->value);
                    });
            }
        );

        if (!$webhook || !$webhook->secret) {
            //Log::warning('Webhook or secret not found for Bitbucket hook', ['hook_uuid' => $hookUuid]);
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawPayload, $webhook->secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function handle(array $payload, array $headers): void
    {
        $eventType = $headers['x-event-key'][0] ?? null;
        match ($eventType) {
            'repo:push' => $this->handlePush($payload),
            'pullrequest:fulfilled' => $this->handlePullRequest($payload), // fulfilled - это слияние
            default => Log::info('Unsupported Bitbucket event type: ' . $eventType),
        };
    }

    private function handlePush(array $payload): void
    {
        $repoName = $payload['repository']['full_name'];
        $integration = $this->findIntegrationById($payload['actor']['uuid'],ServiceConnectionsEnum::BITBUCKET);

        if (!$integration) return;

        foreach ($payload['push']['changes'] as $change) {
            foreach ($change['commits'] as $commit) {
                Log::info(print_r($commit, true));
                $this->activityRepository->updateOrCreateDeveloperActivity([
                    'integration_id' => $integration->id,
                    'type' => 'commit',
                    'external_id' => $commit['hash'],
                    'repository_name' => $repoName,
                    'is_from_provider' => true,
                    'title' => mb_substr($commit['message'], 0, 255),
                    'url' => $commit['links']['html']['href'],
                    'completed_at' => CarbonImmutable::parse($commit['date']),
                    'additions' => 0, // Недоступно в push-событии
                    'deletions' => 0, // Недоступно в push-событии
                ]);
            }
        }
    }

    private function handlePullRequest(array $payload): void
    {
        $pr = $payload['pullrequest'];
        $repoName = $payload['repository']['full_name'];
        $integrationId = $this->findIntegrationById($pr['author']['uuid'],ServiceConnectionsEnum::BITBUCKET);

        if (!$integrationId) return;

        $this->activityRepository->updateOrCreateDeveloperActivity([
            'integration_id' => $integrationId,
            'type' => 'pull_request',
            'external_id' => $pr['id'],
            'repository_name' => $repoName,
            'title' => mb_substr($pr['title'], 0, 255),
            'is_from_provider' => true,
            'url' => $pr['links']['html']['href'],
            'completed_at' => CarbonImmutable::parse($pr['updated_on']),
            // Additions/deletions в Bitbucket нужно получать отдельным запросом к diffstat
            'additions' => 0,
            'deletions' => 0,
        ]);
    }
}
