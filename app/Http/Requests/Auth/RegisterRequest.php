<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_type' => ['required', Rule::in([
                'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
            ])],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Jeton rendu par POST /api/auth/otp/verify ; devient obligatoire
            // quand OTP_REQUIRED_FOR_REGISTRATION=true — sauf pour un numéro
            // étranger, qu'AfrikSMS ne peut pas vérifier (voir aussi
            // AuthController::register(), qui applique la même règle).
            'otp_token' => [$this->otpRequired() ? 'required' : 'nullable', 'string'],
        ];
    }

    private function otpRequired(): bool
    {
        if (! config('otp.required_for_registration')) {
            return false;
        }

        $phone = PhoneNumber::e164((string) $this->input('phone'));

        return str_starts_with($phone, PhoneNumber::DEFAULT_COUNTRY_CODE);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp_token.required' => 'Numéro non vérifié : validez le code reçu par SMS avant de créer le compte.',
        ];
    }
}
