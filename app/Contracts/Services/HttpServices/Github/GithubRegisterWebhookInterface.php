<?php

namespace App\Contracts\Services\HttpServices\Github;

use App\Models\Integration;

interface GithubRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $fullRepoName): void;
}
