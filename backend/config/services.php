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

    // Modulo 4 - Calidad (RF07): microservicio FastAPI con el modelo de IA
    // que diagnostica las fotos de las plantas de cafe.
    'ia' => [
        'url' => env('IA_SERVICE_URL', 'http://127.0.0.1:8001'),
    ],

    // Login con Google (Laravel Socialite). El redirect debe coincidir
    // EXACTO con el que se registra en Google Cloud Console.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/api/auth/google/callback'),
        // A donde se redirige al terminar (la SPA de Quasar), para que la
        // recoja el token desde la URL.
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:9000'),
    ],

];
