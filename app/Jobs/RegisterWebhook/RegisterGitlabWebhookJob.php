<?php

namespace App\Jobs\RegisterWebhook;

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
        readonly private string $repoFullName,
    )
    {
    }

    public function handle(GitlabRegisterWebhookInterface $apiService,UpdateOrCreateWebhookRepositoryInterface $repository)
    {
        $this->executeWithHandling(function () use ($apiService,$repository) {
            $apiService->registerWebhook($this->integration,$this->repoFullName,$repository);
        });
    }
}
