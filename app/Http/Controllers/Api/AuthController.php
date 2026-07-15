<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $user->createToken(
            $request->input('device_name', 'api')
        )->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Authenticate a user by phone or email and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $login)->first();

    // ?-> évite un crash si $user est null. Si null, on utilise un hash bidon
    // pour forcer Hash::check() à s'exécuter quand même (même temps de calcul).

        $hashToCheck = $user?->password ?? '$2y$12$eImiTXuWVxfM37uY4JANjQeQeInvalidHashXyz123456';

        $passwordValid = Hash::check($request->input('password'), $hashToCheck);

        if (! $user || ! $passwordValid) {
            throw ValidationException::withMessages([
                'login' => [__('auth.failed')],
            ]);
        }

        if ($user->status === 'suspended') {
            throw ValidationException::withMessages([
                'login' => ['Ce compte a été suspendu.'],
            ]);
        }

        $token = $user->createToken(
            $request->input('device_name', 'api')
        )->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Revoke the token that was used to authenticate the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
