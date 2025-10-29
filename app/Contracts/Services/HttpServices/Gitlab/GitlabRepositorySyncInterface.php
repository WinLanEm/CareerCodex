<?php

namespace App\Contracts\Services\HttpServices\Gitlab;


use App\Models\Integration;

interface GitlabRepositorySyncInterface
{
    public function syncRepositories(Integration $integration, \Closure $closure): void;
}
