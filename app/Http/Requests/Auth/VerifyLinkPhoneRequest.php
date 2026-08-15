<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Vérification du code envoyé au numéro à associer au compte (voir
 * LinkPhoneRequest) — contrairement à VerifyOtpRequest, `phone` reste
 * obligatoire même si l'utilisateur est connecté : ce n'est pas encore le
 * numéro de son compte.
 */
class VerifyLinkPhoneRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'regex:/^[0-9]{4,8}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Le code de vérification doit être numérique.',
        ];
    }
}
