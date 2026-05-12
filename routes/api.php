<?php

use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\PostController as V1PostController;
use App\Http\Controllers\Api\V1\CommentController as V1CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth endpoints with strict rate limiting (login limiter)
    Route::middleware('throttle:login')->group(function () {
        Route::post('/register', [V1AuthController::class, 'register']);
        Route::post('/login', [V1AuthController::class, 'login']);
    });

    // Public GET endpoints with standard api rate limiting
    Route::middleware('throttle:api')->group(function () {
        Route::get('posts', [V1PostController::class, 'index']);
        Route::get('posts/{post}', [V1PostController::class, 'show']);
        Route::get('posts/{post}/comments', [V1CommentController::class, 'index']);
        Route::get('comments/{comment}', [V1CommentController::class, 'show']);
    });

    // Protected endpoints (write operations) with authentication and write rate limiting
    Route::middleware(['auth:sanctum', 'throttle:write'])->group(function () {
        Route::post('/logout', [V1AuthController::class, 'logout']);
        Route::post('/refresh', [V1AuthController::class, 'refresh']); // POST /refresh as specified in Jobsheet 7 & 8
        
        // Posts (create, update, delete)
        Route::post('posts', [V1PostController::class, 'store']);
        Route::patch('posts/{post}', [V1PostController::class, 'update']);
        Route::delete('posts/{post}', [V1PostController::class, 'destroy']);
        
        // Comments (create, update, delete)
        Route::post('posts/{post}/comments', [V1CommentController::class, 'store']);
        Route::patch('comments/{comment}', [V1CommentController::class, 'update']);
        Route::delete('comments/{comment}', [V1CommentController::class, 'destroy']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware(['auth:sanctum', 'throttle:api']);
});
