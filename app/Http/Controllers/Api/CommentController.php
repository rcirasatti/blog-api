<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->with('user')
            ->latest()
            ->paginate(10);
        
        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->validated('body'),
        ]);

        return response()->json(['data' => new CommentResource($comment->load('user'))], 201);
    }

    public function show(Comment $comment): JsonResponse
    {
        return response()->json(['data' => new CommentResource($comment->load('user', 'post'))]);
    }

    public function update(StoreCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);
        $comment->update($request->validated());
        return response()->json(['data' => new CommentResource($comment)]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json([
            'message' => 'Komentar berhasil dihapus',
            'status_code' => 200
        ], 200);
    }
}
