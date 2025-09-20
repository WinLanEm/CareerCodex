<?php

namespace App\Jobs\SyncDeveloperActivities;

use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SyncGitlabRepositoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private int $projectId,
        readonly private string $repoName,
        readonly private string $defaultBranch,
        readonly private CarbonImmutable $updatedSince
    ) {}

    public function handle(UpdateOrCreateDeveloperActivityInterface $developerActivityRepository): void
    {
        // Логика обработки Rate Limiting будет добавлена позже, пока делаем напрямую
        $this->syncMergedMergeRequests($developerActivityRepository);
        $this->syncCommits($developerActivityRepository);
    }

    private function syncMergedMergeRequests(UpdateOrCreateDeveloperActivityInterface $activityRepository): void
    {
        $response = Http::withToken($this->integration->access_token)
            ->get("https://gitlab.com/api/v4/projects/{$this->projectId}/merge_requests", [
                'state' => 'merged',
                'updated_after' => $this->updatedSince->toIso8601String(),
                'with_stats' => true, // Попросить GitLab сразу включить статистику
            ]);

        $response->throw();
        $mergeRequests = $response->json();

        foreach ($mergeRequests as $mr) {
            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'pull_request',
                'external_id' => $mr['iid'],
                'repository_name' => $this->repoName,
                'title' => $mr['title'],
                'url' => $mr['web_url'],
                'completed_at' => CarbonImmutable::parse($mr['merged_at']),
                'additions' => $mr['stats']['additions'] ?? 0,
                'deletions' => $mr['stats']['deletions'] ?? 0,
            ]);
        }
    }

    private function syncCommits(UpdateOrCreateDeveloperActivityInterface $activityRepository): void
    {
        $response = Http::withToken($this->integration->access_token)
            ->get("https://gitlab.com/api/v4/projects/{$this->projectId}/repository/commits", [
                'ref_name' => $this->defaultBranch,
                'since' => $this->updatedSince->toIso8601String(),
                'with_stats' => true,
            ]);

        $response->throw();
        $commits = $response->json();

        foreach ($commits as $commit) {
            $activityRepository->updateOrCreateDeveloperActivity([
                'integration_id' => $this->integration->id,
                'type' => 'commit',
                'external_id' => $commit['id'],
                'repository_name' => $this->repoName,
                'title' => mb_substr($commit['title'], 0, 255),
                'url' => $commit['web_url'],
                'completed_at' => CarbonImmutable::parse($commit['committed_date']),
                'additions' => $commit['stats']['additions'] ?? 0,
                'deletions' => $commit['stats']['deletions'] ?? 0,
            ]);
        }
    }
}
