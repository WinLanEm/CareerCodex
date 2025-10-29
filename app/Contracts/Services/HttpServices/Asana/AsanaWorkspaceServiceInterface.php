<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Models\Integration;

interface AsanaWorkspaceServiceInterface
{
    public function getProjects(Integration $integration,string $cloudId): array;
    public function getWorkspaces(Integration $integration): array;
}
