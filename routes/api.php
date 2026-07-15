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

        // ?-> évite un crash si $user est null. Si l'utilisateur n'existe pas,
        // on compare quand même avec un hash bidon pour que Hash::check()
        // s'exécute TOUJOURS, avec le même temps de calcul. Ça empêche un
        // attaquant de deviner si un compte existe en mesurant le temps de
        // réponse (timing side-channel / énumération d'utilisateurs).
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

    /**
     * Update the authenticated user's password and revoke all existing tokens.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        // Révoque tous les tokens existants (déconnecte tous les appareils),
        // y compris celui utilisé pour cette requête elle-même.
        $user->tokens()->delete();

        // Génère un nouveau token pour que la session courante reste connectée.
        $newToken = $user->createToken(
            $request->input('device_name', 'api')
        )->plainTextToken;

        return response()->json([
            'message' => 'Mot de passe mis à jour.',
            'token' => $newToken,
        ]);
    }
}