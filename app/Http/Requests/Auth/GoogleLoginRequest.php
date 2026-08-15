<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Jeton OpenID Connect (JWT) rendu par expo-auth-session côté client.
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            // Choisi sur l'écran d'onboarding ; ignoré si un compte existe déjà.
            'user_type' => ['nullable', Rule::in([
                'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
            ])],
        ];
    }
}
