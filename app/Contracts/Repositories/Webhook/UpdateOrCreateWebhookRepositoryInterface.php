<?php

namespace App\Contracts\Repositories\Webhook;

use App\Models\Webhook;

interface UpdateOrCreateWebhookRepositoryInterface
{
    public function updateOrCreateWebhook(array $data): Webhook;
}
