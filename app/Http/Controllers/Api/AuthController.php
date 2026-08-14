<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateMeRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Register a new user and issue an access token.
     *
     * When an `otp_token` (issued by POST /api/auth/otp/verify) is supplied and
     * still valid for that phone, the account starts phone-verified. The token
     * becomes mandatory once OTP_REQUIRED_FOR_REGISTRATION is on.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $otpToken = $data['otp_token'] ?? null;
        unset($data['otp_token']);

        $phoneVerified = $this->otp->redeemRegistrationToken($data['phone'], $otpToken);

        if (! $phoneVerified && config('otp.required_for_registration')) {
            throw ValidationException::withMessages([
                'otp_token' => ['Code de vérification invalide ou expiré. Redemandez un code par SMS.'],
            ]);
        }

        $user = User::create($data + [
            'is_verified' => $phoneVerified,
            'phone_verified_at' => $phoneVerified ? now() : null,
        ]);

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

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => [__('auth.failed')],
            ]);
        }

        if ($user->status === 'suspended') {
            throw ValidationException::withMessages([
                'login' => ['Ce compte a été suspendu.'],
            ]);
        }

        // Demande de suppression : hors délai de grâce, le compte est considéré
        // supprimé (purge imminente) ; dans le délai, on autorise la connexion
        // pour que l'utilisateur puisse annuler sa demande.
        if ($user->hasPendingDeletion() && $user->deletionPurgeAt()->isPast()) {
            throw ValidationException::withMessages([
                'login' => ['Ce compte a été supprimé.'],
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
     * Update the authenticated user's own profile fields.
     */
    public function updateMe(UpdateMeRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json($user);
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
