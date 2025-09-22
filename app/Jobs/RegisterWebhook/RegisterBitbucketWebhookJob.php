<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRegisterWebhookInterface;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterBitbucketWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $repoFullName,
    )
    {
    }

    public function handle(BitbucketRegisterWebhookInterface $apiService)
    {

    }
}
