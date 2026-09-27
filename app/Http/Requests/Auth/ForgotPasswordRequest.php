<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Distincte de SendOtpRequest : ici le compte existe déjà, donc son email
 * enregistré sert de repli pour un numéro étranger (voir
 * PasswordResetController::forgot()) — contrairement à l'inscription ou au
 * rattachement d'un numéro, où aucun compte n'existe encore pour fournir ce
 * repli et l'email doit donc venir de la requête elle-même.
 */
class ForgotPasswordRequest extends FormRequest
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
            'email' => ['nullable', 'email', 'max:255'],
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
