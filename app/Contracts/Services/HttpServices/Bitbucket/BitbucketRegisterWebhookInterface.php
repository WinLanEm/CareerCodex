<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Models\Integration;

interface BitbucketRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $workspaceSlug,string $repoSlug,UpdateOrCreateWebhookRepositoryInterface $repository): void;
}
