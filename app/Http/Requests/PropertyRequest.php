<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
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
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'property_type' => [$required, Rule::in(['room', 'studio', 'apartment', 'house', 'hotel_room'])],
            'address' => [$required, 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price' => [$required, 'numeric', 'min:0'],
            'price_unit' => [$required, Rule::in(['night', 'month'])],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'image_urls' => ['nullable', 'string'],
            'is_available' => ['boolean'],
        ];
    }
}
