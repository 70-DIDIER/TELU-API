<?php

namespace App\Http\Requests;

use App\Services\PayGate;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise le numéro mobile money au format local togolais à 8 chiffres
     * (accepte +228 90 00 00 00, 22890000000, 90 00 00 00…).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_number')) {
            $this->merge(['phone_number' => PhoneNumber::local((string) $this->input('phone_number'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference_type' => ['required', Rule::in(['order', 'reservation', 'subscription'])],
            'reference_id' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::in(array_keys(PayGate::NETWORKS))],
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'Le moyen de paiement doit être flooz ou tmoney (Mixx by Yas).',
            'phone_number.regex' => 'Le numéro mobile money doit être un numéro togolais à 8 chiffres.',
        ];
    }
}
