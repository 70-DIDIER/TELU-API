<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
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
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'profile_photo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
