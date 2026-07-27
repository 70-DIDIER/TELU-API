<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | PayGate Global — mobile money togolais (Flooz & TMoney / Mixx by Yas).
    | Doc: https://paygateglobal.com — le webhook de confirmation doit être
    | déclaré sur le tableau de bord marchand vers `callback_url`.
    */
    'paygate' => [
        'api_key' => env('PAYGATE_API_KEY'),
        'base_url' => env('PAYGATE_BASE_URL', 'https://paygateglobal.com'),
        'callback_url' => env('PAYGATE_CALLBACK_URL', env('APP_URL').'/api/payments/callback'),
        'timeout' => (int) env('PAYGATE_TIMEOUT', 30),
    ],

    /*
    | AfrikSMS — passerelle SMS (codes OTP de vérification de numéro).
    | Le SenderId est limité à 11 caractères et doit être déclaré chez AfrikSMS.
    */
    'afriksms' => [
        // `http` = envoi réel ; `log` = le SMS est seulement écrit dans les logs
        // (développement des clients sans consommer de crédits).
        'driver' => env('AFRIKSMS_DRIVER', 'http'),
        'client_id' => env('AFRIKSMS_CLIENT_ID'),
        'api_key' => env('AFRIKSMS_API_KEY'),
        'base_url' => env('AFRIKSMS_BASE_URL', 'https://api.afriksms.com/api/web/web_v1/outbounds'),
        'sender_id' => env('AFRIKSMS_SENDER_ID', 'TELUBAOBAB'),
        'timeout' => (int) env('AFRIKSMS_TIMEOUT', 20),
    ],

];
