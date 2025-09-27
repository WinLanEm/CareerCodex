<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaRegisterWebhookInterface;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Models\Integration;
use App\Traits\HandlesSyncErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterAsanaWebhookJob implements ShouldQueue
{
    use Queueable, HandlesSyncErrors;

    private string $workspaceGid;
    private string $siteUrl;

    public function __construct(
        readonly private Integration $integration,
        readonly private array $workspace,
        readonly private bool $isFirstRun
    ) {
        $this->workspaceGid = $this->workspace['gid'];
        $this->siteUrl = '';
    }
    public function handle(
        UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository,
        AsanaRegisterWebhookInterface $asanaRegisterWebhook,
        UpdateOrCreateWebhookRepositoryInterface $webhookRepository
    ):void
    {
        $this->executeWithHandling(function () use ($instanceRepository, $asanaRegisterWebhook,$webhookRepository) {
            $webhookData = $asanaRegisterWebhook->registerWebhook($this->integration,$this->workspace);
            $hasWebhook = false;
            if(!empty($webhookData)){
                $hasWebhook = true;
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
            $instance = $instanceRepository->updateOrCreate(
                $this->integration->id,
                $this->workspaceGid,
                $hasWebhook,
                $this->siteUrl
            );

            SyncAsanaInstanceJob::dispatch(
                $instance->id,
                $this->integration,
                $this->isFirstRun,
                $this->workspaceGid,
            )->onQueue('asana');
        });
    }
}
