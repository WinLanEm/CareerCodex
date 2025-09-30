<?php

namespace App\Console\Commands;

use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Jobs\RegularlyRepositorySync\RegularlyGithubRepositorySyncJob;
use App\Jobs\SyncDeveloperRepositories\SyncBitbucketRepositoryJob;
use App\Jobs\SyncDeveloperRepositories\SyncGithubRepositoryJob;
use App\Jobs\SyncDeveloperRepositories\SyncGitlabRepositoryJob;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncServicesInstancesDataCommand extends Command
{
    public function __construct(
        readonly private GetIntegrationInstanceStrategyInterface $providerInstanceStrategy,
    )
    {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-services-instances-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalProcessed = 0;
        IntegrationInstance::where('has_websocket',false)
            ->get()
            ->chunkById(200,function ($integrationInstances) use(&$totalProcessed) {
                $totalProcessed = $integrationInstances->count();
                foreach ($integrationInstances as $integrationInstance) {
                    $integration = $integrationInstance->integration;
                    switch ($integration->provider) {
                        case 'github': RegularlyGithubRepositorySyncJob::dispatch($integrationInstance,$integration); break;
                        case 'gitlab': SyncGitLabRepositoryJob::dispatch($integrationInstance,$integration); break;
                        case 'bitbucket': SyncBitbucketRepositoryJob::dispatch($integrationInstance,$integration); break;
                        case 'jira': SyncJiraInstanceJob::dispatch($integrationInstance,$integration); break;
                        case 'asana': SyncAsanaInstanceJob::dispatch($integrationInstance,$integration); break;
                        default: Log::error("Unknown integration provider '{$integration->provider}'");
                    }
                }
        });
        $this->info("Finished processing all due services. Total processed: {$totalProcessed}");
    }
}
