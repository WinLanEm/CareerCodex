<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SyncAsanaInstanceJob extends SyncInstanceBaseJob
{

    public function __construct(
        Integration $integration,
        bool $isFirstRun,
        private readonly string $cloudId
    ) {
        parent::__construct($integration, $isFirstRun);
    }


    protected function sync(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        CarbonImmutable $updatedSince
    ): void {
        $projects = $this->getProjects();
        foreach ($projects as $project) {
            $this->syncCompletedIssuesForProject(
                $project['gid'],
                $repository,
                $project['name'],
                $updatedSince->toIso8601String()
            );
        }
    }

    private function getProjects(): array
    {
        $allProjects = [];
        $url = 'https://app.asana.com/api/1.0/projects';

        $params = [
            'workspace' => $this->cloudId,
            'limit' => 100,
            'opt_fields' => 'name,gid'
        ];

        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }

            $response = Http::withToken($this->integration->access_token)->timeout($this->timeout)->asJson()->get($url, $params);
            $response->throw();

            $data = $response->json();
            $projectsOnPage = $data['data'] ?? [];

            $allProjects = array_merge($allProjects, $projectsOnPage);

            $nextPageOffset = $data['next_page']['offset'] ?? null;

        } while ($nextPageOffset);

        return $allProjects;
    }
    private function syncCompletedIssuesForProject(
        string $projectKey,
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $achievementUpdateOrCreateRepository,
        string $projectName,
        string $updatedSince
    ): void
    {
        $url = 'https://app.asana.com/api/1.0/tasks';

        $params = [
            'project' => $projectKey,
            'completed_since' => $updatedSince,
            'limit' => 100,
            'opt_fields' => 'name,completed,completed_at,notes,permalink_url'
        ];

        $nextPageOffset = null;

        do {
            if ($nextPageOffset) {
                $params['offset'] = $nextPageOffset;
            }

            $response = Http::withToken($this->integration->access_token)->timeout($this->timeout)->asJson()->get($url, $params);
            $response->throw();

            $data = $response->json();
            $tasks = $data['data'] ?? [];

            foreach ($tasks as $task) {
                if (!$task['completed']) {
                    continue;
                }
                $carbonDate = Carbon::parse($task['completed_at']);
                $achievementUpdateOrCreateRepository->updateOrCreate([
                    'title' => $task['name'],
                    'description' => $task['notes'],
                    'link' => $task['permalink_url'],
                    'date' => $carbonDate,
                    'is_approved' => false,
                    'is_from_provider' => true,
                    'provider' => ServiceConnectionsEnum::ASANA->value,
                    'project_name' => $projectName,
                ]);
            }

            $nextPageOffset = $data['next_page']['offset'] ?? null;
        } while ($nextPageOffset);
    }
}
