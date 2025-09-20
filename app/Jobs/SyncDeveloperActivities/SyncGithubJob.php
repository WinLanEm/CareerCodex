<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGithubJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool $isFirstRun,
    )
    {

    }
    public function handle(): void
    {
        $now = CarbonImmutable::now();
        $updatedSince = $this->isFirstRun
            ? $now->subDays(7)
            : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

        $repositories = $this->fetchUserRepositories();

        foreach ($repositories as $repo) {
            SyncGithubRepositoryJob::dispatch(
                $this->integration,
                $repo['full_name'],
                $repo['default_branch'],
                $updatedSince
            );
        }
    }

    private function fetchUserRepositories():array
    {
        $allRepositories = [];
        $page = 1;
        do {
            $response = Http::withToken($this->integration->access_token)
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
