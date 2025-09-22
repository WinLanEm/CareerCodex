<?php

namespace App\Jobs\RegisterWebhook;

use App\Contracts\Services\HttpServices\Gitlab\GitlabRegisterWebhookInterface;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterGitlabWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly private Integration $integration,
        readonly private string $repoFullName,
    )
    {
    }

    public function handle(GitlabRegisterWebhookInterface $apiService)
    {

    }
}
