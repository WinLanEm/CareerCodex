<?php

namespace App\Console\Commands;

use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Models\Integration;
use App\Models\IntegrationInstance;
use Illuminate\Console\Command;

class SyncServicesInstancesDataCommand extends Command
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
    protected $signature = 'app:sync-services-instances-data-command';

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
        IntegrationInstance::where('has_websocket',true)
            ->get()
            ->chunkById(200,function ($integrationInstances) {

        });
        $totalProcessed = 2;
        $this->info("Finished processing all due services. Total processed: {$totalProcessed}");
    }
}
