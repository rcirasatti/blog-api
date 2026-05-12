<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $post;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->for($this->user)->create();
    }

    /**
     * Test: Get all posts dengan pagination
     */
    public function test_get_all_posts_with_pagination(): void
    {
        Post::factory()->count(15)->for($this->user)->create();

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'body',
                        'author' => ['id', 'name', 'email'],
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data');
    }

    /**
     * Test: Get single post dengan comments
     */
    public function test_get_single_post_with_comments(): void
    {
        $response = $this->getJson("/api/v1/posts/{$this->post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'author' => ['id', 'name', 'email'],
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test: Create post requires authentication
     */
    public function test_create_post_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Test Post',
            'body'  => 'Test body'
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Silakan login terlebih dahulu.');
    }

    /**
     * Test: Create post with valid data and correct token abilities
     */
    public function test_create_post_with_valid_data(): void
    {
        Sanctum::actingAs($this->user, ['post:create']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'New Post',
            'body'  => 'This is a test post content.'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'body', 'author']
            ])
            ->assertJsonPath('data.title', 'New Post');

        $this->assertDatabaseHas('posts', [
            'title' => 'New Post',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test: Create post fails if token lacks post:create ability
     */
    public function test_create_post_fails_without_ability(): void
    {
        Sanctum::actingAs($this->user, ['comment:create']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'New Post',
            'body'  => 'This is a test post content.'
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:create.');
    }

    /**
     * Test: Create post dengan title kosong gagal
     */
    public function test_create_post_with_empty_title_fails(): void
    {
        Sanctum::actingAs($this->user, ['post:create']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => '',
            'body'  => 'This is a test post content.'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonPath('errors.title.0', 'Judul post wajib diisi.');
    }

    /**
     * Test: Update post requires authorization
     */
    public function test_update_post_requires_authorization(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser, ['post:update']);

        $response = $this->patchJson("/api/v1/posts/{$this->post->id}", [
            'title' => 'Updated Title',
            'body'  => 'Updated body'
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
    }

    /**
     * Test: Update post by owner succeeds with correct ability
     */
    public function test_update_post_by_owner_succeeds(): void
    {
        Sanctum::actingAs($this->user, ['post:update']);

        $response = $this->patchJson("/api/v1/posts/{$this->post->id}", [
            'title' => 'Updated Title',
            'body'  => 'Updated body'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'title' => 'Updated Title'
        ]);
    }

    /**
     * Test: Update post fails if token lacks post:update ability
     */
    public function test_update_post_fails_without_ability(): void
    {
        Sanctum::actingAs($this->user, ['post:create']);

        $response = $this->patchJson("/api/v1/posts/{$this->post->id}", [
            'title' => 'Updated Title',
            'body'  => 'Updated body'
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:update.');
    }

    /**
     * Test: Delete post requires authorization
     */
    public function test_delete_post_requires_authorization(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser, ['post:delete']);

        $response = $this->deleteJson("/api/v1/posts/{$this->post->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Delete post by owner succeeds with correct ability
     */
    public function test_delete_post_by_owner_succeeds(): void
    {
        Sanctum::actingAs($this->user, ['post:delete']);

        $response = $this->deleteJson("/api/v1/posts/{$this->post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Post berhasil dihapus');

        $this->assertDatabaseMissing('posts', [
            'id' => $this->post->id
        ]);
    }

    /**
     * Test: Delete post fails if token lacks post:delete ability
     */
    public function test_delete_post_fails_without_ability(): void
    {
        Sanctum::actingAs($this->user, ['post:create']);

        $response = $this->deleteJson("/api/v1/posts/{$this->post->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:delete.');
    }

    /**
     * Test: Get non-existent post returns 404
     */
    public function test_get_non_existent_post_returns_404(): void
    {
        $response = $this->getJson('/api/v1/posts/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Resource tidak ditemukan.');
    }
}
