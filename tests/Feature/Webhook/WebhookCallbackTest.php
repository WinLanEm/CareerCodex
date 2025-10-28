<?php

namespace Tests\Feature\Webhook;

use App\Enums\ServiceConnectionsEnum;
use App\Jobs\Webhook\ProcessWebhookJob;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Bus;

class WebhookCallbackTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_successfully_receives_a_webhook_and_dispatches_job(): void
    {
        Bus::fake();

        $payload = ['test' => 'data'];
        $rawPayload = json_encode($payload);
        $secret = 'test-secret';
        $service = ServiceConnectionsEnum::ASANA;
        $response = $this->postJson(
            route('webhook', ['service' => $service->value, 'secret' => $secret]),
            $payload,
            ['X-Test-Header' => 'value']
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Webhook received']);

        Bus::assertDispatched(ProcessWebhookJob::class, function (ProcessWebhookJob $job) use ($service, $payload, $rawPayload, $secret) {
            $this->assertEquals($service, $this->getProtectedProperty($job, 'service'));
            $this->assertEquals($payload, $this->getProtectedProperty($job, 'payload'));
            $this->assertEquals($rawPayload, $this->getProtectedProperty($job, 'rawPayload'));
            $this->assertEquals($secret, $this->getProtectedProperty($job, 'secret'));
            $headers = $this->getProtectedProperty($job, 'headers');
            $this->assertArrayHasKey('x-test-header', $headers);
            return true;
        });
    }

    public function test_it_handles_asana_secret_handshake(): void
    {
        Bus::fake();
        $secret = 'this-is-secret-from-asana';

        $response = $this->post(route('webhook', ['service' => 'asana']), [], [
            'X-Hook-Secret' => $secret,
        ]);

        $response->assertOk()->assertHeader('X-Hook-Secret', $secret);
        Bus::assertNotDispatched(ProcessWebhookJob::class);
    }

    public function test_it_returns_validation_error_for_unsupported_service(): void
    {
        Bus::fake();
        $response = $this->postJson(route('webhook', ['service' => 'unsupported-service']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('service');
        Bus::assertNotDispatched(ProcessWebhookJob::class);
    }
}
