<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Register a new user
     */
    public function test_register_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe'
        ]);
    }

    /**
     * Test: Login successfully
     */
    public function test_login_user_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /**
     * Test: Login with invalid credentials fails
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    /**
     * Test: Logout user successfully
     */
    public function test_logout_user_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logout berhasil.');
            
        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test: Refresh token successfully
     */
    public function test_refresh_token_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson('/api/v1/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
            
        // Check that old token was revoked (the user should still only have 1 active token)
        $user->refresh();
        $this->assertCount(1, $user->tokens);
    }
}
