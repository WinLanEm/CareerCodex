<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Models\Integration;

interface AsanaRegisterWebhookInterface
{
    public function registerWebhook(Integration $integration,array $project,string $workspaceGid):array;
}
