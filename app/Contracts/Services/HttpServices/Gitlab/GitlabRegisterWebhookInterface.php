<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Models\Integration;

interface GitlabRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $projectId,UpdateOrCreateWebhookRepositoryInterface $repository): void;
}
