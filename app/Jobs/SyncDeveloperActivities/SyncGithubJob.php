<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Jobs\SyncDeveloperRepositories\SyncGithubRepositoryJob;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SyncGithubJob extends SyncGitBaseJob
{
    public function __construct(
        Integration $integration,
    )
    {
        parent::__construct($integration);
    }

    protected function sync(CarbonImmutable $updatedSince,PendingRequest $client): void
    {
        $repositories = $this->fetchUserRepositories($client);

        foreach ($repositories as $repo) {
            SyncGithubRepositoryJob::dispatch(
                $this->integration,
                $repo['default_branch'],
                $updatedSince,
                $repo['full_name'],
            );
        }
    }

    private function fetchUserRepositories(PendingRequest $client):array
    {
        $allRepositories = [];
        $page = 1;
        do {
            $response = $client
                ->get('https://api.github.com/user/repos', [
                    'per_page' => 100,
                    'page' => $page,
                ]);

            $response->throw();

            $repositoriesOnPage = $response->json();
            $allRepositories = array_merge($allRepositories, $repositoriesOnPage);
            $page++;
        } while (!empty($repositoriesOnPage));

        return $allRepositories;
    }
}
