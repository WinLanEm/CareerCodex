<?php

namespace App\Jobs\Webhook;

use App\Contracts\Services\Webhook\WebhookHandlerFactoryInterface;
use App\Enums\ServiceConnectionsEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ServiceConnectionsEnum $service,
        protected array $payload,
        protected string $rawPayload,
        protected array $headers
    ) {}

    public function handle(WebhookHandlerFactoryInterface $handlerFactory): void
    {
        try {
            $handler = $handlerFactory->make($this->service);

            if (!$handler->verify($this->payload,$this->rawPayload, $this->headers)) {
                Log::warning('Webhook verification failed.', ['service' => $this->service->value]);
                return;
            }

            $handler->handle($this->payload, $this->headers);
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'service' => $this->service->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode(),
            ]);
            $this->fail($e);
        }
    }
}
