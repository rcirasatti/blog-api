<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth endpoints with strict rate limiting
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Public GET endpoints (read-only) with moderate rate limiting
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('posts', [PostController::class, 'index']);
        Route::get('posts/{post}', [PostController::class, 'show']);
        Route::get('posts/{post}/comments', [CommentController::class, 'index']);
        Route::get('comments/{comment}', [CommentController::class, 'show']);
    });

    // Protected endpoints (write operations) with strict rate limiting
    Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::patch('/refresh', [AuthController::class, 'refreshToken']);
        
        // Posts (create, update, delete)
        Route::post('posts', [PostController::class, 'store']);
        Route::patch('posts/{post}', [PostController::class, 'update']);
        Route::delete('posts/{post}', [PostController::class, 'destroy']);
        
        // Comments (create, update, delete)
        Route::post('posts/{post}/comments', [CommentController::class, 'store']);
        Route::patch('comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware(['auth:sanctum', 'throttle:60,1']);
});
