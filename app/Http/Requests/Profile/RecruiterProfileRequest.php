<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class RecruiterProfileRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'id_document_url' => ['nullable', 'string', 'max:2048'],
            'rccm_number' => ['nullable', 'string', 'max:255'],
            'company_document_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
