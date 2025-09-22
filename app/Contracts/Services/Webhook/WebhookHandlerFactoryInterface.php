<?php

namespace App\Contracts\Services\Webhook;

use App\Enums\ServiceConnectionsEnum;

interface WebhookHandlerFactoryInterface
{
    public function make(ServiceConnectionsEnum $serviceConnectionsEnum):WebhookHandlerInterface;
}
