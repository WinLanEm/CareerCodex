<?php

namespace Services;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;

class ServiceRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_redirect_fails_validation_for_an_invalid_provider_name()
    {
        $invalidProvider = 'not-a-real-provider';

        $response = $this->getJson(route('auth.redirect', ['provider' => $invalidProvider]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('provider');
    }

    public function test_integration_redirect_fails_validation_for_an_invalid_provider_name()
    {
        $user = User::factory()->create();

        $invalidProvider = 'not-a-real-provider';

        $response = $this->actingAs($user)->getJson(route('service.redirect', ['service' => $invalidProvider]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('service');
    }

    public function test_integration_redirect_fail_with_unauthorized_user()
    {
        $invalidProvider = 'not-a-real-provider';

        $response = $this->getJson(route('service.redirect', ['service' => $invalidProvider]));

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
