<?php

namespace App\Repositories\Webhook;

use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Models\Webhook;

class UpdateOrCreateWebhookRepository implements UpdateOrCreateWebhookRepositoryInterface
{
    public function updateOrCreateWebhook(array $data): Webhook
    {
        return Webhook::updateOrCreate(
            [
                'integration_id' => $data['integration_id'],
                'repository' => $data['repository'],
                'webhook_id' => $data['webhook_id'],
            ],
            [
                'secret' => $data['secret'],
                'repository_id' => $data['repository_id'] ?? $data['repository'],
                'events' => $data['events'],
                'active' => $data['active'],
            ]
        );
    }

}
