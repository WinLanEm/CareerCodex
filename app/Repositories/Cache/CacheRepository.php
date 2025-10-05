<?php

namespace App\Repositories\Cache;

use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use Closure;
use Illuminate\Support\Facades\Cache;

class CacheRepository implements CacheRepositoryInterface
{
    public function remember(string $key, Closure $callback, int $ttl = 3600):mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }
}
