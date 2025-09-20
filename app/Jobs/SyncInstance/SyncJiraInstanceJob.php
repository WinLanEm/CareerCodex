<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Models\Integration;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SyncJiraInstanceJob extends SyncInstanceBaseJob
{
    use Queueable;

    public function __construct(
        Integration $integration,
        bool $isFirstRun,
        private readonly string $cloudId,
        private readonly string $siteUrl
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
                $project['key'],
                $repository,
                $updatedSince
            );
        }
    }

    private function getProjects(): array
    {
        $allProjects = [];
        $startAt = 0;
        $maxResults = 50;

        do {
            $url = "https://api.atlassian.com/ex/jira/{$this->cloudId}/rest/api/3/project/search";
            $response = Http::withToken($this->integration->access_token)->timeout($this->timeout)->asJson()->get($url, [
                'startAt' => $startAt,
                'maxResults' => $maxResults,
            ]);
            $response->throw();

            $data = $response->json();
            $projects = $data['values'] ?? [];
            $allProjects = array_merge($allProjects, $projects);
            $startAt += count($projects);
            $isLast = $data['isLast'] ?? true;
        } while (!$isLast);

        return $allProjects;
    }

    private function syncCompletedIssuesForProject(
        string $projectKey,
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $achievementUpdateOrCreateRepository,
        CarbonImmutable $updatedSince
    )
    {
        $startAt = 0;
        $maxResults = 100;

        $updatedSinceFormatted = $updatedSince->format('Y-m-d H:i');
        $jql = "project = \"{$projectKey}\" AND status = Done AND updated >= \"{$updatedSinceFormatted}\"";

        do {
            $url = "https://api.atlassian.com/ex/jira/{$this->cloudId}/rest/api/3/search";

            $response = Http::withToken($this->integration->access_token)->timeout($this->timeout)->asJson()->get($url, [
                'jql' => $jql,
                'fields' => 'summary,resolutiondate,description,project,issuetype,status',
                'startAt' => $startAt,
                'maxResults' => $maxResults
            ]);

            $response->throw();
            $data = $response->json();
            $issues = $data['issues'] ?? [];
            foreach ($issues as $issue) {
                $descriptionAdf = $issue['fields']['description'] ?? null;
                $descriptionText = $descriptionAdf ? $this->extractTextFromAdf($descriptionAdf) : '';

                $resolutionDateString = $issue['fields']['resolutiondate'];
                $carbonDate = Carbon::parse($resolutionDateString);

                $link = rtrim($this->siteUrl, '/') . '/browse/' . $issue['key'];

                $achievementUpdateOrCreateRepository->updateOrCreate([
                    'title' => $issue['fields']['summary'],
                    'description' => $descriptionText,
                    'date' => $carbonDate,
                    'is_approved' => false,
                    'is_from_provider' => true,
                    'provider' => ServiceConnectionsEnum::JIRA->value,
                    'project_name' => $issue['fields']['project']['name'],
                    'link' => $link,
                ]);
            }

            $startAt += count($issues);

        } while ($startAt < $data['total']);
    }

    private function extractTextFromAdf(array $node): string
    {
        $text = '';
        if (isset($node['type']) && $node['type'] === 'text' && !empty($node['text'])) {
            $text .= $node['text'];
        }

        if (!empty($node['content'])) {
            foreach ($node['content'] as $childNode) {
                $text .= $this->extractTextFromAdf($childNode);
            }
        }

        if (isset($node['type']) && $node['type'] === 'paragraph') {
            $text .= PHP_EOL;
        }

        return $text;
    }
}
