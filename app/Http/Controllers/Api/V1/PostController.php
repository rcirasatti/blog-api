<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')
            ->withCount('comments')
            ->latest()
            ->paginate(10);
        
        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        if (!$request->user()->tokenCan('post:create')) {
            return response()->json([
                'message' => 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:create.'
            ], 403);
        }

        $post = $request->user()->posts()->create($request->validated());
        return response()->json(['data' => new PostResource($post->load('user'))], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(['data' => new PostResource($post->load('user', 'comments.user'))]);
    }

    public function update(StorePostRequest $request, Post $post): JsonResponse
    {
        if (!$request->user()->tokenCan('post:update')) {
            return response()->json([
                'message' => 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:update.'
            ], 403);
        }

        $this->authorize('update', $post);
        $post->update($request->validated());
        return response()->json(['data' => new PostResource($post)]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if (!$request->user()->tokenCan('post:delete')) {
            return response()->json([
                'message' => 'Aksi tidak diizinkan. Token tidak memiliki kemampuan post:delete.'
            ], 403);
        }

        $this->authorize('delete', $post);
        $post->delete();
        return response()->json([
            'message' => 'Post berhasil dihapus',
            'status_code' => 200
        ], 200);
    }
}
