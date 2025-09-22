<?php

namespace App\Repositories\Webhook;

use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Models\Webhook;

class EloquentWebhookRepository implements EloquentWebhookRepositoryInterface
{
    public function find(\Closure $criteria): ?Webhook
    {
        $query = Webhook::query();

        $queryWithCriteria = $criteria($query);

        return $queryWithCriteria->first();
    }
}
