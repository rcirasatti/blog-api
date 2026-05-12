<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create the access and refresh token pair.
     */
    protected function createTokenPair(User $user): array
    {
        $accessExpiry = now()->addMinutes((int) env('SANCTUM_ACCESS_TOKEN_EXPIRATION', 120));
        $refreshExpiry = now()->addMinutes((int) env('SANCTUM_REFRESH_TOKEN_EXPIRATION', 10080));

        $abilities = ['post:create', 'post:update', 'post:delete', 'comment:create', 'comment:delete'];

        $accessTokenObj = $user->createToken('access-token', $abilities, $accessExpiry);
        $refreshTokenObj = $user->createToken('refresh-token', ['token:refresh'], $refreshExpiry);

        return [
            'access_token' => $accessTokenObj->plainTextToken,
            'refresh_token' => $refreshTokenObj->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) env('SANCTUM_ACCESS_TOKEN_EXPIRATION', 120) * 60,
            'refresh_expires_in' => (int) env('SANCTUM_REFRESH_TOKEN_EXPIRATION', 10080) * 60,
            'abilities' => $abilities,
        ];
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create($data);
        $tokenPair = $this->createTokenPair($user);

        return response()->json($tokenPair, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $user = $request->user();
        $tokenPair = $this->createTokenPair($user);

        return response()->json($tokenPair, 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if (!$currentToken || !$currentToken->can('token:refresh')) {
            return response()->json([
                'message' => 'Aksi tidak diizinkan. Token tidak memiliki kemampuan untuk me-refresh token.'
            ], 403);
        }

        $user->tokens()->delete();
        $tokenPair = $this->createTokenPair($user);

        return response()->json($tokenPair, 200);
    }
}
