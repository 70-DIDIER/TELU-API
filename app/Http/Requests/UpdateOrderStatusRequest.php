<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
        // Statuses a vendor is allowed to set. The legality of the transition
        // from the order's current status is enforced in the controller.
        return [
            'status' => ['required', Rule::in(['accepted', 'preparing', 'cancelled'])],
        ];
    }
}
