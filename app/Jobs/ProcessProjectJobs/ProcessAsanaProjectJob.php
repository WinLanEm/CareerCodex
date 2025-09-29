<?php

namespace App\Jobs\ProcessProjectJobs;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaRegisterWebhookInterface;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAsanaProjectJob implements ShouldQueue
{
    use Queueable, Dispatchable, HandlesSyncErrors;

    public function __construct(
        readonly private Integration $integration,
        readonly private array       $project,
        readonly private string      $workspaceGid,
    ) {
    }

    public function handle(
        UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,
        AsanaRegisterWebhookInterface $asanaRegisterWebhook,
        UpdateOrCreateWebhookRepositoryInterface $webhookRepository
    ): void {
        $this->executeWithHandling(function () use ($instanceRepository, $asanaRegisterWebhook,$webhookRepository) {

            $projectGid = $this->project['gid'];
            $webhookData = [];

            $webhookData = $asanaRegisterWebhook->registerWebhook($this->integration, $this->project,$this->workspaceGid);

            $hasWebsocket = false;

            if (!empty($webhookData)) {
                $hasWebsocket = true;
                $webhookRepository->updateOrCreateWebhook([
                    'integration_id' => $webhookData['integration_id'],
                    'repository' => $webhookData['repository'],
                    'repository_id' => $webhookData['repository_id'],
                    'webhook_id' => $webhookData['webhook_id'],
                    'secret' => $webhookData['secret'],
                    'events' => $webhookData['events'],
                    'active' => $webhookData['active'],
                ]);
            }

            $siteUrl = 'https://app.asana.com/0/' . $projectGid . '/list';

            $instance = $instanceRepository->updateOrCreate(
                $this->integration->id,
                $projectGid,
                $hasWebsocket,
                $siteUrl
            );

            SyncAsanaInstanceJob::dispatch(
                $instance->id,
                $this->integration,
                $this->project,
            )->onQueue('asana');
        });
    }
}
