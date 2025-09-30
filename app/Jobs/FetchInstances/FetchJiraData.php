<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Contracts\Services\HttpServices\Jira\JiraRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Jira\JiraWorkspaceServiceInterface;
use App\Jobs\ProcessProjectJobs\ProcessJiraProjectJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class FetchJiraData implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
    )
    {
    }

    public function handle(
        JiraWorkspaceServiceInterface $workspaceApiService,
        JiraProjectServiceInterface $projectApiService,
        JiraRegisterWebhookInterface $jiraRegisterWebhook,
        UpdateOrCreateWebhookRepositoryInterface $webhookRepository,
    ): void {
        $this->executeWithHandling(function () use ($workspaceApiService, $projectApiService,$jiraRegisterWebhook,$webhookRepository) {
            $sites = $workspaceApiService->getWorkspaces($this->integration);

            foreach ($sites as $site) {
                $cloudId = $site['id'];
                $siteUrl = $site['url'];
                $webhookData = $jiraRegisterWebhook->registerWebhook($this->integration, $cloudId, $siteUrl);

                $hasWebhook = !empty($webhookData);

                if ($hasWebhook) {
                    $webhookRepository->updateOrCreateWebhook($webhookData);
                }

                $projects = $projectApiService->getProjects($this->integration, $cloudId);

                foreach ($projects as $project) {
                    ProcessJiraProjectJob::dispatch(
                        $this->integration,
                        $project,
                        $cloudId,
                        $siteUrl,
                        $hasWebhook
                    )->onQueue('jira');
                }
            }
        });
    }
}
