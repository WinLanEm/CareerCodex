<?php

namespace App\Contracts\Services\HttpServices\Jira;

use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;

interface JiraRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,PendingRequest $client,string $cloudId,string $siteUrl):array;
}
