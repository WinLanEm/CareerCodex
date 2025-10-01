<?php

namespace App\Contracts\Services\HttpServices\Gitlab;


interface GitlabRepositorySyncInterface
{
    public function syncRepositories(string $token, \Closure $closure): void;
}
