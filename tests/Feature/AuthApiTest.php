<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Every API response must contain Security Headers
     */
    protected function assertSecurityHeaders($response)
    {
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

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
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'refresh_expires_in',
                'abilities'
            ]);

        $this->assertSecurityHeaders($response);

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
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'refresh_expires_in',
                'abilities'
            ]);

        $this->assertSecurityHeaders($response);
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

        $this->assertSecurityHeaders($response);
    }

    /**
     * Test: Logout user successfully
     */
    public function test_logout_user_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('access-token', ['post:create'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->assertSecurityHeaders($response);
            
        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    /**
     * Test: Refresh token using valid refresh token succeeds
     */
    public function test_refresh_token_successfully(): void
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('refresh-token', ['token:refresh'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $refreshToken")
            ->postJson('/api/v1/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'refresh_expires_in',
                'abilities'
            ]);

        $this->assertSecurityHeaders($response);
            
        // Old token was deleted, but new pair was generated (2 tokens)
        $user->refresh();
        $this->assertCount(2, $user->tokens);
    }

    /**
     * Test: Refresh token using access token (lacks token:refresh ability) fails
     */
    public function test_refresh_token_fails_with_access_token(): void
    {
        $user = User::factory()->create();
        $accessToken = $user->createToken('access-token', ['post:create'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $accessToken")
            ->postJson('/api/v1/refresh');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Aksi tidak diizinkan. Token tidak memiliki kemampuan untuk me-refresh token.');

        $this->assertSecurityHeaders($response);
    }
}
