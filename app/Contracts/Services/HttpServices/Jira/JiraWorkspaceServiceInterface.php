<?php

namespace App\Contracts\Services\HttpServices\Jira;

use Illuminate\Http\Client\PendingRequest;

interface JiraWorkspaceServiceInterface
{
    public function getWorkspaces(string $token,PendingRequest $client): array;
}
