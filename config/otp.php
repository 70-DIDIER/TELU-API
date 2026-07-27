<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vérification par code OTP (SMS AfrikSMS)
    |--------------------------------------------------------------------------
    */

    // Longueur du code envoyé par SMS.
    'length' => (int) env('OTP_LENGTH', 4),

    // Durée de validité du code, en minutes.
    'ttl_minutes' => (int) env('OTP_TTL_MINUTES', 10),

    // Délai minimum avant de pouvoir redemander un code, en secondes.
    'resend_delay_seconds' => (int) env('OTP_RESEND_DELAY', 60),

    // Nombre maximum de codes demandés par numéro et par heure.
    'max_per_hour' => (int) env('OTP_MAX_PER_HOUR', 5),

    // Nombre d'essais autorisés sur un même code avant invalidation.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    // Durée de validité du jeton remis après vérification, en minutes.
    'token_ttl_minutes' => (int) env('OTP_TOKEN_TTL_MINUTES', 30),

    // Exiger un numéro vérifié par OTP pour créer un compte.
    // À passer à true une fois les clients (mobile, back-office) câblés.
    'required_for_registration' => (bool) env('OTP_REQUIRED_FOR_REGISTRATION', false),

    // Gabarit du SMS ; :code est remplacé par le code généré.
    'message' => env('OTP_MESSAGE', 'TELU BAOBAB : votre code de verification est :code. Il expire dans :minutes minutes. Ne le partagez avec personne.'),

];
