<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class SyncJiraInstanceJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors, Dispatchable;

    public function __construct(
        readonly protected int         $instanceId,
        readonly protected Integration $integration,
        readonly protected string       $projectKey,
        readonly protected string       $projectName,
        readonly protected string      $cloudId,
        readonly protected string      $siteUrl
    ) {}

    public function handle(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        JiraProjectServiceInterface $apiService,
        UpdateIntegrationRepositoryInterface $integrationRepository
    ) {
        $this->executeWithHandling(function () use ($repository, $apiService, $integrationRepository) {
            $apiService->syncCompletedIssuesForProject(
                $repository,
                $this->integration,
                $this->projectKey,
                $this->cloudId,
                function ($issue) use ($repository) {
                    $descriptionAdf = $issue['fields']['description'] ?? null;
                    $descriptionText = $descriptionAdf ? $this->extractTextFromAdf($descriptionAdf) : '';
                    $resolutionDateString = $issue['fields']['resolutiondate'];
                    if (!$resolutionDateString) return;

                    $carbonDate = Carbon::parse($resolutionDateString);
                    $link = rtrim($this->siteUrl, '/') . '/browse/' . $issue['key'];

                    $repository->updateOrCreate([
                        'title' => $issue['fields']['summary'],
                        'description' => $descriptionText,
                        'date' => $carbonDate,
                        'is_approved' => false,
                        'is_from_provider' => true,
                        'integration_instance_id' => $this->instanceId,
                        'project_name' => $this->projectName,
                        'link' => $link,
                    ]);
                }
            );
        });
    }

    private function extractTextFromAdf(mixed $node): string
    {
        if (!$node) return '';
        if (is_string($node)) return $node;
        if (!is_array($node)) return '';
        $text = '';
        if (($node['type'] ?? null) === 'text' && !empty($node['text'])) {
            $text .= $node['text'];
        }
        if (!empty($node['content'])) {
            foreach ($node['content'] as $childNode) {
                $text .= $this->extractTextFromAdf($childNode);
            }
        }
        if (($node['type'] ?? null) === 'paragraph' && trim($text) !== '') {
            $text .= PHP_EOL;
        }
        return $text;
    }
}
