<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Code OTP envoyé par email — canal utilisé pour un numéro étranger
 * (AfrikSMS ne couvre que le Togo), voir OtpService::issue().
 */
class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre code de vérification TELU BAOBAB');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-code');
    }
}
