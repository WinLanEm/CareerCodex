<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Models\Integration;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class SyncGitBaseJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue;

    public function __construct(
        protected Integration $integration,
    ) {}

    public function handle():void
    {
        try {
            $updatedSince = CarbonImmutable::now()->subDays(7);
            $client = Http::withToken($this->integration->access_token);
            $this->sync($updatedSince,$client);
        }catch (Exception $exception){
            Log::info("Job failed during git sync",[
                'job' => static::class,
                'integration_id' => $this->integration->id,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'line' => $exception->getLine(),
            ]);
            $this->fail($exception);
        }
    }

    abstract protected function sync(CarbonImmutable $updatedSince,PendingRequest $client): void;
}
