<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Phone Number ID
    |--------------------------------------------------------------------------
    |
    | Your WhatsApp Business Phone Number ID from Meta Business Manager.
    | You can find this in your WhatsApp Business API settings.
    |
    */
    'phone_id' => env('WHATSAPP_PHONE_ID'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Token
    |--------------------------------------------------------------------------
    |
    | Your permanent access token for WhatsApp Business API.
    | Generate this from your Meta Business App settings.
    |
    */
    'token' => env('WHATSAPP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verify Token
    |--------------------------------------------------------------------------
    |
    | A secure token you create to verify webhook requests from WhatsApp.
    | This must match the token you configure in Meta Business Manager.
    |
    */
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The WhatsApp Cloud API version to use.
    | See: https://developers.facebook.com/docs/graph-api/changelog
    |
    */
    'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for API responses.
    |
    */
    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed requests.
    | retry_times: Number of retry attempts
    | retry_delay: Delay between retries in milliseconds
    |
    */
    'retry_times' => env('WHATSAPP_RETRY_TIMES', 3),
    'retry_delay' => env('WHATSAPP_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Auto Mark Messages as Read
    |--------------------------------------------------------------------------
    |
    | Automatically mark incoming messages as read when webhook is processed.
    |
    */
    'mark_messages_as_read' => env('WHATSAPP_MARK_AS_READ', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook route settings.
    |
    */
    'webhook' => [
        'prefix' => env('WHATSAPP_WEBHOOK_PREFIX', 'webhook'),
        'middleware' => ['api'],
    ],
];
