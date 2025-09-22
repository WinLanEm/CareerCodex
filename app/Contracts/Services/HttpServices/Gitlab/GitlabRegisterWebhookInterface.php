<?php

namespace App\Contracts\Services\HttpServices\Gitlab;

use App\Models\Integration;

interface GitlabRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $fullRepoName): void;
}
