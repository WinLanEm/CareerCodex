<?php

namespace App\Contracts\Services\HttpServices\Bitbucket;

use App\Models\Integration;

interface BitbucketRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $workspaceSlug,string $repoSlug,string $repositoryId): array;
}
