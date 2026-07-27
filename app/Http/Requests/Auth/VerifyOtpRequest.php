<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `phone` n'est requis que sur le flux public : un utilisateur connecté
     * vérifie forcément le numéro de son propre compte.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => [$this->user() ? 'nullable' : 'required', 'string', 'max:30'],
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
