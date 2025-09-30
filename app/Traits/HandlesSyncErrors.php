<?php

namespace App\Traits;

use App\Exceptions\ApiRateLimitExceededException;
use Exception;
use Illuminate\Support\Facades\Log;

trait HandlesSyncErrors
{
    protected function executeWithHandling(\Closure $callback): void
    {
        try {
            $callback();
        }catch (ApiRateLimitExceededException $e) {
            $this->release($e->retryAfter);
        } catch (Exception $e) {
            Log::error("Job failed", [
                'job' => static::class,
                'integration_id' => $this->integration->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->fail($e);
        }
    }
}
