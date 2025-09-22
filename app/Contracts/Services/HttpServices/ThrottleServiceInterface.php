<?php

namespace App\Contracts\Services\HttpServices;

use App\Enums\ServiceConnectionsEnum;

interface ThrottleServiceInterface
{
    public static function for(ServiceConnectionsEnum $service,\Closure $closure);
}
