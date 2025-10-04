<?php

namespace Tests\Feature\Webhook;

use App\Contracts\Services\Webhook\WebhookHandlerFactoryInterface;
use App\Contracts\Services\Webhook\WebhookHandlerInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Jobs\Webhook\ProcessWebhookJob;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
{
    public function test_handle_executes_handler_on_successful_verification(): void
    {
        $service = ServiceConnectionsEnum::GITHUB;
        $payload = ['data' => 'test'];
        $rawPayload = json_encode($payload);
        $headers = ['X-GitHub-Event' => 'push'];

        $handlerMock = Mockery::mock(WebhookHandlerInterface::class);
        $handlerMock->shouldReceive('verify')
            ->once()
            ->with($payload, $rawPayload, $headers, null)
            ->andReturn(true);
        $handlerMock->shouldReceive('handle')
            ->once()
            ->with($payload, $headers);

        $factoryMock = Mockery::mock(WebhookHandlerFactoryInterface::class);
        $factoryMock->shouldReceive('make')
            ->once()
            ->with($service)
            ->andReturn($handlerMock);

        $job = new ProcessWebhookJob($service, $payload, $rawPayload, $headers);
        $job->handle($factoryMock);
    }

    public function test_handle_stops_if_verification_fails(): void
    {
        Log::spy();
        $service = ServiceConnectionsEnum::GITHUB;
        $payload = ['data' => 'test'];
        $rawPayload = json_encode($payload);
        $headers = [];

        $handlerMock = Mockery::mock(WebhookHandlerInterface::class);
        $handlerMock->shouldReceive('verify')
            ->once()
            ->andReturn(false);

        $handlerMock->shouldNotReceive('handle');

        $factoryMock = Mockery::mock(WebhookHandlerFactoryInterface::class);
        $factoryMock->shouldReceive('make')
            ->once()
            ->andReturn($handlerMock);

        $job = new ProcessWebhookJob($service, $payload, $rawPayload, $headers);
        $job->handle($factoryMock);

        Log::shouldHaveReceived('warning')->once()->with('Webhook verification failed.', ['service' => 'github']);
    }

    public function test_handle_logs_error_and_fails_on_exception(): void
    {
        Log::spy();
        $service = ServiceConnectionsEnum::GITHUB;
        $exception = new \Exception('Something went wrong');

        $handlerMock = Mockery::mock(WebhookHandlerInterface::class);
        $handlerMock->shouldReceive('verify')
            ->once()
            ->andThrow($exception);

        $factoryMock = Mockery::mock(WebhookHandlerFactoryInterface::class);
        $factoryMock->shouldReceive('make')
            ->once()
            ->andReturn($handlerMock);

        $job = Mockery::mock(ProcessWebhookJob::class, [$service, [], '', []])->makePartial();
        $job->shouldReceive('fail')->once()->with($exception);

        $job->handle($factoryMock);

        Log::shouldHaveReceived('error')->once();
    }
}
