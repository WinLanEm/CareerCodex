<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRegisterWebhookInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterGitlabWebhookJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $repoId,
        readonly private string $repoFullName,
        readonly private string $webUrl,
    )
    {
    }

    public function handle(
        GitlabRegisterWebhookInterface $apiService,
        UpdateOrCreateWebhookRepositoryInterface $repository,
        UpdateOrCreateIntegrationInstanceRepositoryInterface $integrationRepository,
    ):void
    {
        $this->executeWithHandling(function () use ($apiService,$repository,$integrationRepository) {
            $webhookData = $apiService->registerWebhook($this->integration,$this->repoId,$this->repoFullName);
            if(empty($webhookData)){
                $integrationRepository->updateOrCreate(
                    $this->integration->id,
                    $this->repoId,
                    false,
                    $this->webUrl,
                );
            }else{
                $integrationRepository->updateOrCreate(
                    $this->integration->id,
                    $this->repoId,
                    true,
                    $this->webUrl,
                );
                $repository->updateOrCreateWebhook(
                    [
                        'integration_id' => $webhookData['integration_id'],
                        'repository' => $webhookData['repository'],
                        'repository_id' => $webhookData['repository_id'],
                        'webhook_id' => $webhookData['webhook_id'],
                        'secret' => $webhookData['secret'],
                        'events' => $webhookData['events'],
                        'active' => $webhookData['active'],
                    ]
                );
            }
        });
    }
}
