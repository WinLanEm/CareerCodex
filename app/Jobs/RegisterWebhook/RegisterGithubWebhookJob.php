<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Services\HttpServices\Github\GithubRegisterWebhookInterface;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterGithubWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $repoFullName,
    )
    {
    }

    public function handle(GithubRegisterWebhookInterface $apiService)
    {

    }
}
