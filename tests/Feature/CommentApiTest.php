<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $post;
    protected $comment;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->for($this->user)->create();
        $this->comment = Comment::factory()->for($this->post)->for($this->user)->create();
    }

    /**
     * Test: Get comments for post dengan pagination
     */
    public function test_get_comments_for_post_with_pagination(): void
    {
        Comment::factory()->count(15)->for($this->post)->for($this->user)->create();

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'body',
                        'author' => ['id', 'name', 'email'],
                        'post_id',
                        'created_at'
                    ]
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data');
    }

    /**
     * Test: Get single comment
     */
    public function test_get_single_comment(): void
    {
        $response = $this->getJson("/api/comments/{$this->comment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'body',
                    'author' => ['id', 'name', 'email'],
                    'post_id'
                ]
            ]);
    }

    /**
     * Test: Create comment requires authentication
     */
    public function test_create_comment_requires_authentication(): void
    {
        $response = $this->postJson("/api/posts/{$this->post->id}/comments", [
            'body' => 'Great post!'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Create comment with valid data
     */
    public function test_create_comment_with_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'body' => 'This is a great post!'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'body', 'author', 'post_id']
            ])
            ->assertJsonPath('data.body', 'This is a great post!');

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a great post!',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test: Create comment with empty body fails
     */
    public function test_create_comment_with_empty_body_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'body' => ''
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.body.0', 'Isi komentar wajib diisi.');
    }

    /**
     * Test: Create comment with body too short fails
     */
    public function test_create_comment_with_body_too_short_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'body' => 'Hi'
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.body.0', 'Isi komentar minimal 3 karakter.');
    }

    /**
     * Test: Create comment with body too long fails
     */
    public function test_create_comment_with_body_too_long_fails(): void
    {
        $longBody = str_repeat('a', 1001);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'body' => $longBody
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.body.0', 'Isi komentar maksimal 1000 karakter.');
    }

    /**
     * Test: Update comment requires authorization
     */
    public function test_update_comment_requires_authorization(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->patchJson("/api/comments/{$this->comment->id}", [
                'body' => 'Updated comment'
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Update comment by owner succeeds
     */
    public function test_update_comment_by_owner_succeeds(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/comments/{$this->comment->id}", [
                'body' => 'Updated comment text'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.body', 'Updated comment text');
    }

    /**
     * Test: Delete comment requires authorization
     */
    public function test_delete_comment_requires_authorization(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/comments/{$this->comment->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Delete comment by owner succeeds
     */
    public function test_delete_comment_by_owner_succeeds(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/comments/{$this->comment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Komentar berhasil dihapus');

        $this->assertDatabaseMissing('comments', [
            'id' => $this->comment->id
        ]);
    }

    /**
     * Test: Get non-existent comment returns 404
     */
    public function test_get_non_existent_comment_returns_404(): void
    {
        $response = $this->getJson('/api/comments/9999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Resource tidak ditemukan.');
    }
}
