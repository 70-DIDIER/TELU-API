<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'price' => [$required, 'numeric', 'min:0'],
            'duration_days' => [$required, 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'subscriber_type' => [$required, Rule::in(['vendor', 'driver'])],
        ];
    }
}
