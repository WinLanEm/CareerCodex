<?php

namespace App\Contracts\Services\HttpServices\Jira;


interface JiraWorkspaceServiceInterface
{
    public function getWorkspaces(string $token): array;
}
