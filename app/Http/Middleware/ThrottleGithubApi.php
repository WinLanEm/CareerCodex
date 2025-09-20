<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class ThrottleGithubApi
{
    public function handle($job, $next)
    {
        Redis::throttle('github_api')
            ->allow(50)
            ->every(60)
            ->then(function () use ($job, $next) {
                $next($job);
            },
                function () use ($job) {
                    $job->release(5);
                });
    }
}
