<?php

namespace App\Jobs\RegisterWebhook;

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
        readonly private string $repoFullName,
    )
    {
    }

    public function handle(GithubRegisterWebhookInterface $apiService,UpdateOrCreateWebhookRepositoryInterface $repository): void
    {
        $this->executeWithHandling(function () use ($apiService,$repository) {
            $apiService->registerWebhook($this->integration, $this->repoFullName,$repository);
        });
    }
}
