<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Webhook de confirmation envoyé par PayGate Global après un paiement.
 * Le corps n'est pas signé : il sert uniquement de déclencheur, l'état réel
 * est re-vérifié auprès de l'API PayGate avant toute écriture.
 */
class PayGateCallbackRequest extends FormRequest
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
            'identifier' => ['required', 'string'],
            'tx_reference' => ['nullable', 'string'],
            'payment_reference' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric'],
            'datetime' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
        ];
    }
}
