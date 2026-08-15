<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Envoi d'un code OTP vers un numéro que l'utilisateur connecté (compte créé
 * par connexion sociale, sans téléphone) souhaite associer à son compte.
 */
class LinkPhoneRequest extends FormRequest
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
        ];
    }

    /**
     * Numéro de compte normalisé en E.164 sans "+" (22890112233, 33612345678…).
     */
    public function internationalPhone(): string
    {
        return PhoneNumber::e164($this->validated()['phone']);
    }
}
