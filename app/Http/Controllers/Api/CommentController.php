<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post): JsonResponse
    {
        $comments = $post->comments()->with('user')->latest()->get();
        return response()->json($comments);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate(['body' => 'required|string']);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function show(Comment $comment): JsonResponse
    {
        return response()->json($comment->load('user', 'post'));
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);
        $data = $request->validate(['body' => 'required|string']);
        $comment->update($data);
        return response()->json($comment);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json(null, 204);
    }
}
