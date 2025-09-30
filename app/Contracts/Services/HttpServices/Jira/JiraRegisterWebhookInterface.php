<?php

namespace App\Contracts\Services\HttpServices\Jira;

use App\Models\Integration;

interface JiraRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,string $cloudId,string $siteUrl):array;
}
