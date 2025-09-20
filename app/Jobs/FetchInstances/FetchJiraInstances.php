<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchJiraInstances implements ShouldQueue
{
    use Queueable, Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        readonly private Integration $integration,
        readonly private bool        $isFirstRun
    )
    {
    }

    public function handle(UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository): void
    {
        try {
            $sites = $this->getSites($this->integration->access_token);
            $this->processSitesAndDispatchJobs($sites,$instanceRepository);
        }catch (\Exception $exception){
            Log::error("Failed to get Jira provider instances for connection ID {$this->integration->id}",[
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ]);
            $this->fail($exception);
        }
    }

    private function getSites(string $token): array
    {
        $providerInstanceUrl = config('services.jira_integration.provider_instance_url');
        $response = Http::withToken($token)
            ->get($providerInstanceUrl);
        $response->throw();

        return $response->json();
    }

    private function processSitesAndDispatchJobs(array $sites, UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository): void
    {
        foreach ($sites as $site) {
            $cloudId = $site['id'];

            $instanceRepository->updateOrCreate(
                $this->integration->id,
                $cloudId,
                $site['url']
            );

            SyncJiraInstanceJob::dispatch(
                $this->integration,
                $this->isFirstRun,
                $cloudId,
                $site['url']
            );
        }
    }
}
