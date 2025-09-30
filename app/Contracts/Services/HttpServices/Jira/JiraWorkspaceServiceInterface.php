<?php

namespace App\Contracts\Services\HttpServices\Jira;


use App\Models\Integration;

interface JiraWorkspaceServiceInterface
{
    public function getWorkspaces(Integration $integration): array;
}
