<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'reference_type' => ['required', Rule::in(['order', 'reservation', 'subscription'])],
            'reference_id' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::in(['flooz', 'tmoney', 'card', 'mobile_money'])],
        ];
    }
}
