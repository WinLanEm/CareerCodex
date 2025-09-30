<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ApiRateLimitExceededException extends Exception
{
    readonly public int $retryAfter;

    public function __construct(string $message = "", int $retryAfter = 60, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }
}
