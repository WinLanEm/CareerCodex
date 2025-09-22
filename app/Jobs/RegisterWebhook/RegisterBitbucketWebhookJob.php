<?php

namespace App\Jobs\RegisterWebhook;

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
        readonly private string $repoSlug
    )
    {
    }

    public function handle(BitbucketRegisterWebhookInterface $apiService, UpdateOrCreateWebhookRepositoryInterface $repository)
    {
        $this->executeWithHandling(function () use ($apiService,$repository) {
            $apiService->registerWebhook($this->integration, $this->workspaceSlug,$this->repoSlug,$repository);
        });
    }
}
