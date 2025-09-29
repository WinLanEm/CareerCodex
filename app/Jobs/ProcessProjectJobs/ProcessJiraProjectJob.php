<?php

namespace App\Jobs\ProcessProjectJobs;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Jobs\SyncInstance\SyncJiraInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessJiraProjectJob implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;


    public function __construct(
        readonly private Integration $integration,
        readonly private array       $project,
        readonly private bool        $isFirstRun,
        readonly private string      $cloudId,
        readonly private string      $siteUrl,
        readonly private bool        $hasWebhook,
    ) {}

    public function handle(
        UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,
    ): void {
        $this->executeWithHandling(function () use ($instanceRepository) {
            $projectKey = $this->project['key'];

            $instance = $instanceRepository->updateOrCreate(
                $this->integration->id,
                $projectKey,
                $this->hasWebhook,
                rtrim($this->siteUrl, '/') . '/browse/' . $projectKey, // Прямая ссылка на проект
            );

            SyncJiraInstanceJob::dispatch(
                $instance->id,
                $this->integration,
                $this->isFirstRun,
                $this->project,
                $this->cloudId,
                $this->siteUrl
            )->onQueue('jira');
        });
    }
}
