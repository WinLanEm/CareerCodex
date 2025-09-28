<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Contracts\Services\HttpServices\Jira\JiraRegisterWebhookInterface;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use App\Traits\HandlesSyncErrors;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncJiraInstanceJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors;

    public function __construct(
        readonly protected Integration $integration,
        readonly protected bool $isFirstRun,
        readonly protected string $cloudId,
        readonly protected string $siteUrl
    ) {}

    public function handle(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        JiraProjectServiceInterface $apiService,
        UpdateIntegrationInstanceRepositoryInterface $integrationRepository,
        UpdateOrCreateWebhookRepositoryInterface $webhookRepository,
        JiraRegisterWebhookInterface $jiraRegisterWebhook,
        UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,
    ):void
    {
        $this->executeWithHandling(
            function () use ($repository, $apiService, $integrationRepository,$jiraRegisterWebhook,$webhookRepository,$instanceRepository) {
                $client = Http::withToken($this->integration->access_token);

                if ($this->isFirstRun) {
                    $webhookData = $jiraRegisterWebhook->registerWebhook($this->integration, $client, $this->cloudId, $this->siteUrl);

                    $hasWebhook = !empty($webhookData);

                    if ($hasWebhook) {
                        $webhookRepository->updateOrCreateWebhook($webhookData);
                    }

                    $instance = $instanceRepository->updateOrCreate(
                        $this->integration->id,
                        $this->cloudId,
                        $hasWebhook,
                        $this->siteUrl
                    );

                } else {
                    $instance = IntegrationInstance::where('integration_id', $this->integration->id)
                        ->where('external_id', $this->cloudId)
                        ->first();
                    if (!$instance) {
                        Log::critical('Jira Integration Instance not found on non-first run!', [
                            'integration_id' => $this->integration->id,
                            'cloud_id' => $this->cloudId,
                        ]);
                        return;
                    }
                }

                $now = CarbonImmutable::now();
                $updatedSince = $this->isFirstRun
                    ? $now->subDays(7)
                    : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

                $this->sync($repository, $apiService, $updatedSince, $client,$instance->id);
                $this->updateNextCheckTime($integrationRepository, $now);
            }
        );
    }


    protected function sync(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        JiraProjectServiceInterface $apiService,
        CarbonImmutable $updatedSince,
        PendingRequest $client,
        int $instanceId
    ): void {
        $projects = $apiService->getProjects($this->integration->access_token, $this->cloudId, $client);
        foreach ($projects as $project) {
            $apiService->syncCompletedIssuesForProject(
                $repository,
                $updatedSince,
                $this->integration->access_token,
                $project['key'],
                $this->cloudId,
                $client,
                function ($issue) use ($repository,$instanceId) {
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
                        'integration_instance_id' => $instanceId,
                        'project_name' => $issue['fields']['project']['name'],
                        'link' => $link,
                    ]);
                }
            );
        }
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

    private function updateNextCheckTime(UpdateIntegrationInstanceRepositoryInterface $repository, CarbonImmutable $checkTime): void
    {
        $repository->update($this->integration, [
            'next_check_provider_instances_at' => $checkTime->addHour()
        ]);
    }
}
