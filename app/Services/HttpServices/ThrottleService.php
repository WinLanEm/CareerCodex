<?php

namespace App\Services\HttpServices;

use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Exceptions\ApiRateLimitExceededException;
use Illuminate\Support\Facades\Redis;

class ThrottleService implements ThrottleServiceInterface
{
    public static function for(ServiceConnectionsEnum $service,\Closure $closure)
    {
        $config = config('throttle.limits.' . $service->value);
        return Redis::throttle('throttle-' . $service->value)
            ->allow($config['allow'])
            ->every($config['every'])
            ->block($config['block'] ?? 10)
            ->then(
                fn() => $closure(),
                fn() => throw new ApiRateLimitExceededException(strtoupper($service->value) . ' API rate limit exceeded.', $config['retryAfter'] ?? 30)
            );
    }
}
