<?php

namespace App\Services\IntegrationInstanceStrategy;

use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Jobs\FetchInstances\FetchAsanaInstances;
use App\Jobs\FetchInstances\FetchJiraInstances;
use App\Jobs\SyncDeveloperActivities\SyncBitbucketJob;
use App\Jobs\SyncDeveloperActivities\SyncGithubJob;
use App\Jobs\SyncDeveloperActivities\SyncGitlabJob;
use App\Models\Integration;
use InvalidArgumentException;

class GetIntegrationInstanceStrategy implements GetIntegrationInstanceStrategyInterface
{
    public function getInstance(Integration $integration, bool $isFirstRun = false):void
    {
        switch ($integration->service) {
            case ServiceConnectionsEnum::JIRA->value:
                FetchJiraInstances::dispatch($integration,$isFirstRun)->onQueue('jira');
                break;
            case ServiceConnectionsEnum::ASANA->value:
                FetchAsanaInstances::dispatch($integration,$isFirstRun)->onQueue('asana');
                break;
            case ServiceConnectionsEnum::GITHUB->value:
                SyncGithubJob::dispatch($integration)->onQueue('github');
                break;
            case ServiceConnectionsEnum::GITLAB->value:
                SyncGitlabJob::dispatch($integration)->onQueue('gitlab');
                break;
            case ServiceConnectionsEnum::BITBUCKET->value:
                SyncBitbucketJob::dispatch($integration)->onQueue('bitbucket');
                break;
            default: throw new InvalidArgumentException("Unsupported provider service $integration->service");
        };
    }
}
