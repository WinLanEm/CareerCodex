<?php

namespace App\Contracts\Repositories\Webhook;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

interface EloquentWebhookRepositoryInterface
{
    public function find(\Closure $closure): ?Webhook;

    public function findAll(\Closure $closure): Collection;
}
