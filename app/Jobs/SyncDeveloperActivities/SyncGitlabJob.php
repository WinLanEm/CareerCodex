<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Jobs\SyncDeveloperRepositories\SyncGitlabRepositoryJob;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SyncGitlabJob extends SyncGitBaseJob
{
    public function __construct(
        protected Integration $integration,
    ) {
        parent::__construct($integration);
    }

    protected function sync(CarbonImmutable $updatedSince, PendingRequest $client): void
    {
        $projects = $this->fetchUserProjects($client);

        foreach ($projects as $project) {
            SyncGitlabRepositoryJob::dispatch(
                $this->integration,
                $project['default_branch'],
                $updatedSince,
                $project['id'], // GitLab часто использует ID проекта для API-запросов
                $project['path_with_namespace'],
            );
        }
    }


    private function fetchUserProjects(PendingRequest $client): array
    {
        $allProjects = [];
        $page = 1;
        do {
            $response = $client
                ->get('https://gitlab.com/api/v4/projects', [
                    'membership' => true, // Получить все проекты, где пользователь является участником
                    'per_page' => 100,
                    'page' => $page,
                ]);

            $response->throw();

            $projectsOnPage = $response->json();
            $allProjects = array_merge($allProjects, $projectsOnPage);
            $page++;

        } while ($response->header('X-Next-Page')); // GitLab удобно сообщает о наличии следующей страницы

        return $allProjects;
    }
}
