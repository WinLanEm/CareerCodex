<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRegisterWebhookInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterBitbucketWebhookJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $workspaceSlug,
        readonly private string $repoSlug,
        readonly private string $repositoryId,
        readonly private string $webUrl,
        readonly private string $defaultBranch
    )
    {
    }

    public function handle(
        BitbucketRegisterWebhookInterface $apiService,
        UpdateOrCreateWebhookRepositoryInterface $repository,
        UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository)
    {
        $this->executeWithHandling(function () use ($apiService,$repository,$instanceRepository) {
            $webhookData = $apiService->registerWebhook($this->integration, $this->workspaceSlug,$this->repoSlug,$this->repositoryId);
            if(empty($webhookData)){
                $instanceRepository->updateOrCreate(
                    $this->integration->id,
                    $this->repositoryId,
                    false,
                    $this->webUrl,
                    "$this->workspaceSlug/$this->repoSlug",
                    $this->defaultBranch
                );
            }else{
                $instanceRepository->updateOrCreate(
                    $this->integration->id,
                    $this->repositoryId,
                    true,
                    $this->webUrl,
                    "$this->workspaceSlug/$this->repoSlug",
                    $this->defaultBranch
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
