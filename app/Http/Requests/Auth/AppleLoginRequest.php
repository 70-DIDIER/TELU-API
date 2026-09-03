<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppleLoginRequest extends FormRequest
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
            // Jeton d'identité (JWT) rendu par expo-apple-authentication côté client.
            'identity_token' => ['required', 'string'],
            // Nom complet : Apple ne le renvoie qu'à la toute première autorisation,
            // le client le transmet quand il est disponible.
            'full_name' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            // Choisi sur l'écran d'onboarding ; ignoré si un compte existe déjà.
            'user_type' => ['nullable', Rule::in([
                'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
            ])],
        ];
    }
}
