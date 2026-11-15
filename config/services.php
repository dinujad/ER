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

    'hikvision' => [
        'app_key' => env('HIKVISION_APP_KEY'),
        'app_secret' => env('HIKVISION_APP_SECRET'),
        'base_url' => env('HIKVISION_BASE_URL'),
    ],

    'textlk' => [
        'api_key' => env('TEXTLK_API_KEY'),
        'sender_id' => env('TEXTLK_SENDER_ID', env('APP_NAME', 'Laravel')),
        'url' => env('TEXTLK_URL', 'https://app.text.lk/api/v3/sms/send'),
    ],

    /*
     * HostGrap WhatsApp API (https://wa-api.hostgrap.com). Prefer ERP → Cheque / WhatsApp settings in DB;
     * env vars are optional fallbacks for CI / first deploy.
     */
    'hostgrap' => [
        'email' => env('HOSTGRAP_WHATSAPP_EMAIL'),
        'api_key' => env('HOSTGRAP_WHATSAPP_API_KEY'),
        'admin_phone' => env('HOSTGRAP_ADMIN_WHATSAPP_PHONE'),
        'send_message_url' => env('HOSTGRAP_SEND_MESSAGE_URL', 'https://wa-api.hostgrap.com/api/send-message.php'),
        'send_image_url' => env('HOSTGRAP_SEND_IMAGE_URL', 'https://wa-api.hostgrap.com/api/send-image.php'),
    ],

    /*
     * Google Maps Platform API (API name: Maps JavaScript API / Places API).
     * Set GOOGLE_MAPS_API_KEY in .env for future use (e.g. location autocomplete, map display for driver start point / destination).
     */
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
