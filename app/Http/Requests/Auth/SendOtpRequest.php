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
            // AfrikSMS ne couvre que le Togo : un numéro étranger reçoit son
            // code par email, d'où l'adresse exigée dans ce cas (voir OtpService).
            'email' => [$this->isForeignPhone() ? 'required' : 'nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Numéro de compte normalisé en E.164 sans "+" (22890112233, 33612345678…).
     */
    public function internationalPhone(): string
    {
        return PhoneNumber::e164($this->validated()['phone']);
    }

    private function isForeignPhone(): bool
    {
        $phone = PhoneNumber::e164((string) $this->input('phone'));

        return $phone !== '' && ! PhoneNumber::isTogo($phone);
    }
}
