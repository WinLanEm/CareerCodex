<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Github\GithubRegisterWebhookInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterGithubWebhookJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $fullName,
        readonly private string $webUrl,
    )
    {
    }

    public function handle(GithubRegisterWebhookInterface $apiService,
                           UpdateOrCreateWebhookRepositoryInterface $repository,
                            UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository): void
    {
        $this->executeWithHandling(function () use ($apiService,$repository,$instanceRepository) {
            $webhookData = $apiService->registerWebhook($this->integration, $this->fullName);
            if(empty($webhookData)){
                $instanceRepository->updateOrCreate(
                    $this->integration->id,
                    $this->fullName,
                    false,
                    $this->webUrl,
                );
            }else{
                $instanceRepository->updateOrCreate(
                    $this->integration->id,
                    $this->fullName,
                    true,
                    $this->webUrl,
                );
                $repository->updateOrCreateWebhook(
                    [
                        'integration_id' => $webhookData['integration_id'],
                        'repository' => $webhookData['repository'],
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
