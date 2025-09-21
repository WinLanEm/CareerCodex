<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Jobs\SyncDeveloperRepositories\SyncBitbucketRepositoryJob;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SyncBitbucketJob extends SyncGitBaseJob
{
    public function __construct(
        protected Integration $integration,
    ) {
        parent::__construct($integration);
    }

    protected function sync(CarbonImmutable $updatedSince, PendingRequest $client): void
    {
        $repositories = $this->fetchUserRepositories($client);

        foreach ($repositories as $repo) {
            SyncBitbucketRepositoryJob::dispatch(
                $this->integration,
                $repo['mainbranch']['name'] ?? 'main',
                $updatedSince,
                $repo['workspace']['slug'],
                $repo['slug'],
            );
        }
    }

    private function fetchUserRepositories(PendingRequest $client): array
    {
        $allRepositories = [];
        $nextPageUrl = "https://api.bitbucket.org/2.0/repositories?role=member";

        do {
            $response = $client->get($nextPageUrl);
            $response->throw();

            $pageJson = $response->json();
            $allRepositories = array_merge($allRepositories, $pageJson['values'] ?? []);
            $nextPageUrl = $pageJson['next'] ?? null;
        } while ($nextPageUrl);

        return $allRepositories;
    }
}
