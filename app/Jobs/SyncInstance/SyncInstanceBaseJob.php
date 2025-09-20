<?php

namespace App\Jobs\SyncInstance;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

abstract class SyncInstanceBaseJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        protected Integration $integration,
        protected bool $isFirstRun
    ) {}

    public function handle(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $achievementRepository,
        UpdateIntegrationInstanceRepositoryInterface $integrationRepository
    ): void
    {
        try {
            $now = CarbonImmutable::now();

            $updatedSince = $this->isFirstRun
                ? $now->subDays(7)
                : CarbonImmutable::parse($this->integration->next_check_provider_instances_at)->subHour();

            $this->sync($achievementRepository, $updatedSince);

            $this->updateNextCheckTime($integrationRepository, $now);
        } catch (\Exception $e) {
            Log::error("Sync failed for integration ID {$this->integration->id}", [
                'service' => $this->integration->service,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->fail($e);
        }
    }

    abstract protected function sync(
        WorkspaceAchievementUpdateOrCreateRepositoryInterface $repository,
        CarbonImmutable $updatedSince
    ): void;

    private function updateNextCheckTime(UpdateIntegrationInstanceRepositoryInterface $repository, CarbonImmutable $checkTime): void
    {
        $repository->update($this->integration, [
            'next_check_provider_instances_at' => $checkTime->addHour()
        ]);
    }
}
