<?php

namespace App\Contracts\Services\HttpServices\Github;


use App\Models\Integration;

interface GithubRepositorySyncInterface
{
    public function syncRepositories(Integration $integration, \Closure $closure): void;
}
