<?php

namespace App\Repositories\Webhook;

use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

class EloquentWebhookRepository implements EloquentWebhookRepositoryInterface
{
    public function find(\Closure $closure): ?Webhook
    {
        $query = Webhook::query();

        $queryWithCriteria = $closure($query);

        return $queryWithCriteria->first();
    }
    public function findAll(\Closure $closure): Collection
    {
        $query = Webhook::query();
        $queryWithCriteria = $closure($query);
        return $queryWithCriteria->get();
    }
}
