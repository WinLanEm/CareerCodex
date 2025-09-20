<?php

namespace App\Jobs\FetchInstances;

use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Jobs\SyncInstance\SyncAsanaInstanceJob;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAsanaInstances implements ShouldQueue
{
    use Queueable, Dispatchable;

    public function __construct(
        readonly private Integration $integration,
        readonly private bool        $isFirstRun
    )
    {
    }

    public function handle(UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository): void
    {
        try {
            $workspaces = $this->getWorkspaces($this->integration->access_token);
            $this->makeProviderInstance($workspaces, $instanceRepository);
        } catch (\Exception $exception) {
            Log::error("Failed to get Asana provider instances for connection ID {$this->integration->id}", [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ]);
            $this->fail($exception);
        }
    }


    private function getWorkspaces(string $token): array
    {
        $providerInstanceUrl = config('services.asana_integration.provider_instance_url');
        $response = Http::withToken($token)
            ->withHeaders(['accept' => 'application/json'])
            ->get($providerInstanceUrl);

        $response->throw();

        // Asana возвращает данные в ключе "data"
        return $response->json()['data'];
    }


    private function makeProviderInstance(array $workspaces, UpdateOrCreateIntegrationInstanceRepositoryInterface $instanceRepository)
    {
        foreach ($workspaces as $workspace) {
            $workspaceGid = $workspace['gid']; // В Asana это 'gid'

            // В Asana нет прямого URL для workspace в API-ответе,
            $siteUrl = 'https://app.asana.com/0/' . $workspaceGid . '/list';

            $instanceRepository->updateOrCreate(
                $this->integration->id,
                $workspaceGid,
                $siteUrl
            );

            SyncAsanaInstanceJob::dispatch(
                $this->integration,
                $this->isFirstRun,
                $workspaceGid
            );
        }
    }
}
