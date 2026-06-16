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

    // ──────────────────────────────────────────────────────────────────────
    // EN: Telegram bot — instant alerts + confirm/reject bookings from chat.
    //     Optional: if 'token'/'chat_id' are empty, the notifier is a no-op.
    // ES: Bot de Telegram — avisos al instante + confirmar/rechazar desde el
    //     chat. Opcional: si 'token'/'chat_id' van vacíos, el notificador no
    //     hace nada (no rompe). Ver docs/TELEGRAM.md para obtener los valores.
    // ──────────────────────────────────────────────────────────────────────
    'telegram' => [
        'token'          => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'        => env('TELEGRAM_CHAT_ID'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // ──────────────────────────────────────────────────────────────────────
    // EN: Google Calendar + Meet — creates the event for each confirmed booking
    //     and, if online, an auto Meet link. Optional: if client_id/secret/
    //     refresh_token are empty the service is a no-op (bookings still work).
    // ES: Google Calendar + Meet — crea el evento de cada cita confirmada y, si
    //     es online, un enlace de Meet automático. Opcional: si client_id/secret/
    //     refresh_token van vacíos, el servicio no hace nada (las citas siguen
    //     funcionando). Ver docs/GOOGLE.md para obtener los valores.
    // ──────────────────────────────────────────────────────────────────────
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
        'calendar_id'   => env('GOOGLE_CALENDAR_ID', 'primary'),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://localhost'),
    ],

];
