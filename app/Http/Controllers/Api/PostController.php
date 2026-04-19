<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = Post::with('user')->latest()->get();
        return response()->json($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->validated());
        return response()->json($post->load('user'), 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post->load('user', 'comments.user'));
    }

    public function update(StorePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);
        $post->update($request->validated());
        return response()->json($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json(null, 204);
    }
}
