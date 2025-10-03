<?php

namespace App\Contracts\Services\HttpServices\Github;


interface GithubRepositorySyncInterface
{
    public function syncRepositories(string $token, \Closure $closure): void;
}
