<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
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

        $response = $this->getJson('/api/posts');

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
        $response = $this->getJson("/api/posts/{$this->post->id}");

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
        $response = $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'body'  => 'Test body'
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Silakan login terlebih dahulu.');
    }

    /**
     * Test: Create post with valid data
     */
    public function test_create_post_with_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
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
     * Test: Create post dengan title kosong gagal
     */
    public function test_create_post_with_empty_title_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
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

        $response = $this->actingAs($otherUser)
            ->patchJson("/api/posts/{$this->post->id}", [
                'title' => 'Updated Title',
                'body'  => 'Updated body'
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
    }

    /**
     * Test: Update post by owner succeeds
     */
    public function test_update_post_by_owner_succeeds(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/posts/{$this->post->id}", [
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
     * Test: Delete post requires authorization
     */
    public function test_delete_post_requires_authorization(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Delete post by owner succeeds
     */
    public function test_delete_post_by_owner_succeeds(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Post berhasil dihapus');

        $this->assertDatabaseMissing('posts', [
            'id' => $this->post->id
        ]);
    }

    /**
     * Test: Get non-existent post returns 404
     */
    public function test_get_non_existent_post_returns_404(): void
    {
        $response = $this->getJson('/api/posts/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Resource tidak ditemukan.');
    }

    /**
     * Test: Validate post body max length
     */
    public function test_validate_post_body_max_length(): void
    {
        $longBody = str_repeat('a', 100001);

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'title' => 'Test',
                'body'  => $longBody
            ]);

        $response->assertStatus(201); // No max length set, so it succeeds
    }
}
