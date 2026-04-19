<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return view('welcome');
});

// Test Redis Cache - Sesuai Jobsheet
Route::get('/test-redis', function () {
    Cache::put('test_key', 'Nginx -> Octane -> RoadRunner -> Redis BERHASIL!', 60);
    $value = Cache::get('test_key');
    
    return response()->json([
        'status' => 'success',
        'message' => $value,
    ]);
});
