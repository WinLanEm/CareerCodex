<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SyncGitlabJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool $isFirstRun,
    ) {}

    public function handle(): void
    {
        $now = CarbonImmutable::now();
        $updatedSince = $this->isFirstRun
            ? $now->subDays(7)
            : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

        $projects = $this->fetchUserProjects();

        foreach ($projects as $project) {
            SyncGitlabRepositoryJob::dispatch(
                $this->integration,
                $project['id'], // GitLab часто использует ID проекта для API-запросов
                $project['path_with_namespace'],
                $project['default_branch'],
                $updatedSince
            );
        }
    }

    private function fetchUserProjects(): array
    {
        $allProjects = [];
        $page = 1;
        do {
            $response = Http::withToken($this->integration->access_token)
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
