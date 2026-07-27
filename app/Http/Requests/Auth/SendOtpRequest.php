<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * Numéro normalisé au format international sans préfixe (22890112233).
     */
    public function internationalPhone(): string
    {
        return PhoneNumber::international($this->validated()['phone']);
    }
}
