<?php

namespace App\Contracts\Services\Webhook;

interface WebhookHandlerInterface
{
    public function verify(array $payload,string $rawPayload, array $headers,string $secret): bool;

    public function handle(array $payload, array $headers): void;
}
