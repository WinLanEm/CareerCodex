<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\ServiceConnectionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use App\Jobs\Webhook\ProcessWebhookJob;

class WebhookCallbackController extends Controller
{
    public function __invoke(ValidateServiceIntegrationRequest $request,string $service)
    {
        $serviceEnum = ServiceConnectionsEnum::tryFrom($service);
        if (!$serviceEnum) {
            return new MessageResource('Service not supported',false,404);
        }

        $payload = $request->all();
        $headers = $request->header();

        ProcessWebhookJob::dispatch($serviceEnum, $payload, $headers)
            ->onQueue('webhooks');

        return new MessageResource('Webhook received');
    }
}
