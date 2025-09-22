<?php

namespace App\Contracts\Services\HttpServices\Github;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Models\Integration;

interface GithubRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $fullRepoName,UpdateOrCreateWebhookRepositoryInterface $repository): void;
}
