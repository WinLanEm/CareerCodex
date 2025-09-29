<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SyncJiraInstanceJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors, Dispatchable;

    public function __construct(
        readonly protected int         $instanceId,
        readonly protected Integration $integration,
        readonly protected bool        $isFirstRun,
        readonly protected array       $project,
        readonly protected string      $cloudId,
        readonly protected string      $siteUrl
    ) {}

    public function handle(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        JiraProjectServiceInterface $apiService,
        UpdateIntegrationInstanceRepositoryInterface $integrationRepository
    ) {
        $this->executeWithHandling(function () use ($repository, $apiService, $integrationRepository) {
            $now = CarbonImmutable::now();

            $updatedSince = $this->isFirstRun
                ? $now->subDays(7)
                : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

            $this->sync($repository, $apiService, $updatedSince);

            $this->updateNextCheckTime($integrationRepository, $now);
        });
    }

    private function sync(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        JiraProjectServiceInterface $apiService,
        CarbonImmutable $updatedSince,
    ): void {
        $apiService->syncCompletedIssuesForProject(
            $repository,
            $updatedSince,
            $this->integration,
            $this->project['key'],
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
                    'project_name' => $this->project['name'],
                    'link' => $link,
                ]);
            }
        );
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

    private function updateNextCheckTime(UpdateIntegrationInstanceRepositoryInterface $repository, CarbonImmutable $checkTime): void
    {
        $repository->update($this->integration, [
            'next_check_provider_instances_at' => $checkTime->addHour()
        ]);
    }
}
