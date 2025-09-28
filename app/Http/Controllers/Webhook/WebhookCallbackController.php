<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\ServiceConnectionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Services\ValidateServiceIntegrationRequest;
use App\Http\Resources\MessageResource;
use App\Jobs\Webhook\ProcessWebhookJob;


class WebhookCallbackController extends Controller
{
    public function __invoke(ValidateServiceIntegrationRequest $request,string $service, ?string $secret = null)
    {
        $serviceEnum = ServiceConnectionsEnum::tryFrom($service);
        if (!$serviceEnum) {
            return new MessageResource('Service not supported',false,404);
        }
        if ($serviceEnum === ServiceConnectionsEnum::ASANA && $request->hasHeader('X-Hook-Secret')) {
            $secret = $request->header('X-Hook-Secret');
            return response(null, 200)->header('X-Hook-Secret', $secret);
        }

        $rawPayload = $request->getContent();
        $payload = json_decode($rawPayload, true);
        $headers = $request->header();

        ProcessWebhookJob::dispatch($serviceEnum, $payload,$rawPayload, $headers,$secret)
            ->onQueue('webhooks');

        return new MessageResource('Webhook received');
    }
}
