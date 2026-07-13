<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class JobSeekerProfileRequest extends FormRequest
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
            'profession' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'availability' => ['nullable', 'string', 'max:255'],
        ];
    }
}
