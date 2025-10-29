<?php

namespace App\Traits;

use App\Exceptions\ApiRateLimitExceededException;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

trait HandlesGitSyncErrors
{
    protected function executeWithHandling(\Closure $callback): void
    {
        try {
            // Выполняем основную логику, переданную из джобы
            $callback();
        }catch (ApiRateLimitExceededException $e) {
            $this->release($e->retryAfter);
        }catch (RequestException $e) {
            if ($e->response->status() === 409) {
                // просто ничего не делаем и позволяем джобе успешно завершиться пустой репозиторий.
                return;
            }
            throw $e;
        }catch (Exception $e) {
            Log::error("Job failed during git sync", [
                'job' => static::class,
                'integration_id' => $this->integration->id,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->fail($e);
        }
    }
}
