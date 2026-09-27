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
            // Requis seulement si le numéro est étranger (code envoyé par email,
            // AfrikSMS ne couvrant que le Togo) ET que le compte n'a pas déjà
            // d'adresse — un compte social en a le plus souvent une.
            'email' => [
                $this->isForeignPhone() && ! $this->user()?->email ? 'required' : 'nullable',
                'email', 'max:255',
            ],
        ];
    }

    /**
     * Numéro de compte normalisé en E.164 sans "+" (22890112233, 33612345678…).
     */
    public function internationalPhone(): string
    {
        return PhoneNumber::e164($this->validated()['phone']);
    }

    private function isForeignPhone(): bool
    {
        $phone = PhoneNumber::e164((string) $this->input('phone'));

        return $phone !== '' && ! PhoneNumber::isTogo($phone);
    }
}
