<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('posts', PostController::class);
    Route::apiResource('posts.comments', CommentController::class)->shallow();
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
