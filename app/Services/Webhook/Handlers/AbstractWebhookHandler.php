<?php

namespace App\Services\Webhook\Handlers;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Repositories\Integrations\FindIntegrationByClosureRepositoryInterface;
use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Services\Webhook\WebhookHandlerInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use App\Repositories\Integrations\FindIntegrationByClosureRepository;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        protected UpdateOrCreateDeveloperActivityInterface $activityRepository,
        protected EloquentWebhookRepositoryInterface $webhookRepository,
        protected FindIntegrationByClosureRepositoryInterface $integrationRepository,
        protected WorkspaceAchievementUpdateOrCreateRepositoryInterface $achievementRepository,
    ) {
    }

    abstract public function verify(array $payload,string $rawPayload, array $headers): bool;

    abstract public function handle(array $payload, array $headers): void;

    protected function findIntegrationById(string|int $externalUserId, ServiceConnectionsEnum $service): ?Integration
    {
        return $this->integrationRepository->findIntegrationByClosure(
            function (Builder $query) use ($externalUserId, $service) {
                return $query->where('service_user_id', $externalUserId)
                    ->where('service', $service->value);
            }
        );
    }
}

