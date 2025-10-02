<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttachPasswordUserTest extends TestCase
{
    use RefreshDatabase;
    public function test_a_user_can_attach_their_password()
    {
        $oldPassword = 'password123';
        $newPassword = 'newPassword123';

        $user = User::factory()->create(['password' => $oldPassword]);

        $oldPasswordHash = $user->password;

        $response = $this->actingAs($user)->postJson(route('attach.password'),[
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => true,
                'message' => 'Password updated successfully'
            ]);

        $user->refresh();

        $this->assertNotEquals($oldPasswordHash, $user->password);

        $this->assertTrue(Hash::check($newPassword, $user->password));

        $this->assertFalse(Hash::check($oldPassword, $user->password));
    }
    public function test_a_unauthorized_user_can_not_attach_their_password()
    {
        $oldPassword = 'password123';
        $newPassword = 'newPassword123';

        $user = User::factory()->create(['password' => $oldPassword]);

        $response = $this->postJson(route('attach.password'),[
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
