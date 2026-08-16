<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DriverProfileRequest extends FormRequest
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
            'vehicle_type' => [$required, 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'coverage_zone' => ['nullable', 'string', 'max:255'],
            'is_available' => ['boolean'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'id_document_url' => ['nullable', 'string', 'max:2048'],
            'vehicle_photo_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
