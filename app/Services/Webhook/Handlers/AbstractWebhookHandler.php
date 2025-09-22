<?php

namespace App\Services\Webhook\Handlers;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Services\Webhook\WebhookHandlerInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Repositories\Integrations\FindIntegrationByClosureRepository;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        protected UpdateOrCreateDeveloperActivityInterface $activityRepository,
        protected EloquentWebhookRepositoryInterface $webhookRepository,
        protected FindIntegrationByClosureRepository $integrationRepository
    ) {
    }

    abstract public function verify(array $payload, array $headers): bool;

    abstract public function handle(array $payload, array $headers): void;

    protected function findIntegrationId(string|int $externalUserId, ServiceConnectionsEnum $service): ?int
    {
        $integration = $this->integrationRepository->findIntegrationByClosure(
            function (Builder $query) use ($externalUserId, $service) {
                return $query->where('external_user_id', $externalUserId)
                    ->where('service', $service->value);
            }
        );

        return $integration?->id;
    }
}

