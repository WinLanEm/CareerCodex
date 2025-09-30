<?php

namespace App\Console\Commands;

use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Models\Integration;
use Illuminate\Console\Command;

class SyncServicesDataCommand extends Command
{
    public function __construct(
        readonly private GetIntegrationInstanceStrategyInterface $providerInstanceStrategy,
    )
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-services-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = 200;
        $totalProcessed = 0;
        Integration::query()
            ->chunkById($chunkSize, function ($services) use (&$totalProcessed) {
                $totalProcessed += $services->count();
                foreach ($services as $service) {
                    $this->providerInstanceStrategy->getInstance($service);
                }
            });

        $this->info("Finished processing all due services. Total processed: {$totalProcessed}");
    }
}
