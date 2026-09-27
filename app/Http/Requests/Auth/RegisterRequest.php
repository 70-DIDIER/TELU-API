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
            // Obligatoire pour un numéro étranger : c'est l'adresse à laquelle
            // part le code OTP, AfrikSMS ne couvrant que le Togo (voir
            // OtpService::issue()) — sans elle, ce compte ne pourrait jamais
            // être vérifié ni récupéré en cas de mot de passe oublié.
            'email' => [$this->isForeignPhone() ? 'required' : 'nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_type' => ['required', Rule::in([
                'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
            ])],
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Jeton rendu par POST /api/auth/otp/verify ; devient obligatoire
            // quand OTP_REQUIRED_FOR_REGISTRATION=true, pour tout numéro (un
            // numéro étranger est désormais vérifiable par email — voir
            // AuthController::register(), qui applique la même règle).
            'otp_token' => [$this->otpRequired() ? 'required' : 'nullable', 'string'],
        ];
    }

    private function isForeignPhone(): bool
    {
        $phone = PhoneNumber::e164((string) $this->input('phone'));

        return $phone !== '' && ! PhoneNumber::isTogo($phone);
    }

    private function otpRequired(): bool
    {
        return (bool) config('otp.required_for_registration');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp_token.required' => 'Numéro non vérifié : validez le code reçu par SMS ou par email avant de créer le compte.',
            'email.required' => 'Une adresse email est requise pour un numéro étranger.',
        ];
    }
}
