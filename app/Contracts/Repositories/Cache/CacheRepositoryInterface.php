<?php

namespace App\Contracts\Repositories\Cache;

use Closure;

interface CacheRepositoryInterface
{
    public function remember(string $key, Closure $callback, int $ttl = 3600):mixed;
}
