<?php

namespace App\Contracts\Services\HttpServices\Asana;

use Illuminate\Http\Client\PendingRequest;

interface AsanaWorkspaceServiceInterface
{
    public function getWorkspaces(string $token,PendingRequest $client): array;
}
