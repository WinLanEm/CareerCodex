<?php

namespace App\Jobs\SyncDeveloperRepositories;


use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class SyncRepositoryBaseJob implements ShouldQueue
{
    use Queueable;

    protected int $maxActivities = 10;

    public function __construct(
        protected Integration $integration,
        protected string $defaultBranch,
        protected CarbonImmutable $updatedSince
    )
    {}

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository):void
    {
        try {
            $client = Http::withToken($this->integration->access_token);
            $this->sync($developerActivityRepository,$client);
        }catch (RequestException $e){
            if ($e->response->status() === 409) {
                //пустой репозиторий, не надо обрабатывать
            } else {
                throw $e;
            }
        }
        catch (Exception $exception){
            Log::info("Job failed during repository sync",[
                'job' => static::class,
                'integration_id' => $this->integration->id,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'line' => $exception->getLine(),
            ]);
            $this->fail($exception);
        }
    }
    abstract protected function sync(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository,PendingRequest $client): void;
}
