<?php

namespace App\Repositories\Webhook;

use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Models\Webhook;

class EloquentWebhookRepository implements EloquentWebhookRepositoryInterface
{
    public function find(\Closure $closure): ?Webhook
    {
        $query = Webhook::query();

        $queryWithCriteria = $closure($query);

        return $queryWithCriteria->first();
    }
}
