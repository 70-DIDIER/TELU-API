<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class VendorProfileRequest extends FormRequest
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
            'shop_name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'id_document_url' => ['nullable', 'string', 'max:2048'],
            'rccm_number' => ['nullable', 'string', 'max:255'],
            'rccm_document_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
