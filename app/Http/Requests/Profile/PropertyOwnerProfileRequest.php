<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyOwnerProfileRequest extends FormRequest
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
            'owner_type' => [$required, Rule::in(['individual', 'hotel'])],
            // A hotel must carry a name; an individual owner may omit it.
            'company_name' => ['nullable', 'required_if:owner_type,hotel', 'string', 'max:255'],
        ];
    }
}
