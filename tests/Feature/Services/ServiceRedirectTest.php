<?php

namespace Services;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class ServiceRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_an_error_if_socialite_driver_fails()
    {
        $providerName = 'github';

        Socialite::shouldReceive('driver')
            ->with($providerName)
            ->andThrow(new \Exception('Driver init failed'));

        $response = $this->getJson(route('auth.redirect', ['provider' => $providerName]));

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Failed to redirect to the provider']);
    }

    public function test_it_fails_validation_for_an_invalid_provider_name()
    {
        $invalidProvider = 'not-a-real-provider';

        $response = $this->getJson(route('auth.redirect', ['provider' => $invalidProvider]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('provider');
    }
}
