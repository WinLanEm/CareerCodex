<?php

namespace App\Console\Commands;

use App\Enums\ServiceConnectionsEnum;
use App\Jobs\SyncDeveloperRepositories\SyncBitbucketRepositoryJob;
use App\Jobs\SyncDeveloperRepositories\SyncGithubRepositoryJob;
use App\Jobs\SyncDeveloperRepositories\SyncGitlabRepositoryJob;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\IntegrationInstance;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncServicesInstancesDataCommand extends Command
{
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
        $updatedSince = CarbonImmutable::now()->subDays(7);
        $totalProcessed = 0;
        IntegrationInstance::where('has_websocket',false)
            ->chunkById(200,function ($integrationInstances) use(&$totalProcessed,$updatedSince) {
                $totalProcessed += $integrationInstances->count();
                foreach ($integrationInstances as $integrationInstance) {
                    $integration = $integrationInstance->integration;
                    switch ($integration->service) {
                        case ServiceConnectionsEnum::GITHUB->value: {
                            SyncGithubRepositoryJob::dispatch(
                                $integration,
                                $integrationInstance->default_branch,
                                $updatedSince,
                                $integrationInstance->repository_name
                            )->onQueue('github');
                            break;
                        }
                        case ServiceConnectionsEnum::GITLAB->value: {
                            SyncGitLabRepositoryJob::dispatch(
                                $integration,
                                $integrationInstance->default_branch,
                                $updatedSince,
                                $integrationInstance->external_id,
                                $integrationInstance->repository_name
                            )->onQueue('gitlab');
                            break;
                        }
                        case ServiceConnectionsEnum::BITBUCKET->value: {
                            $slug = explode('/',$integrationInstance->repository_name);
                            if(count($slug) != 2){
                                Log::error("Bitbucket slug error $integrationInstance->repository_name");
                                break;
                            }
                            SyncBitbucketRepositoryJob::dispatch(
                                $integration,
                                $integrationInstance->default_branch,
                                $updatedSince,
                                $slug[0],
                                $slug[1],
                            )->onQueue('bitbucket');
                            break;
                        }
                        case ServiceConnectionsEnum::JIRA->value: {
                            $meta = $integrationInstance->meta ?? [];
                            $cloudId = $meta['cloudId'] ?? null;
                            if($cloudId === null){
                                Log::error("Jira cloud id not found",$meta);
                                break;
                            }
                            SyncJiraInstanceJob::dispatch(
                                $integrationInstance->id,
                                $integration,
                                $integrationInstance->external_id,
                                $integrationInstance->repository_name,
                                $cloudId,
                                $integrationInstance->site_url
                            )->onQueue('jira');
                            break;
                        }
                        case ServiceConnectionsEnum::ASANA->value: {
                            SyncAsanaInstanceJob::dispatch(
                                $integrationInstance->id,
                                $integration,
                                $integrationInstance->external_id,
                                $integrationInstance->repository_name,
                            )->onQueue('asana');
                            break;
                        }
                        default: Log::error("Unknown integration provider '{$integration->provider}'");
                    }
                }
        });
        $this->info("Finished processing all due services. Total processed: {$totalProcessed}");
    }
}
