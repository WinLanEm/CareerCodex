<?php

namespace App\Contracts\Repositories\Webhook;

use App\Models\Webhook;

interface EloquentWebhookRepositoryInterface
{
    public function find(\Closure $criteria): ?Webhook;
}
