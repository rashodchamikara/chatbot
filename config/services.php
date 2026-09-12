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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'meta' => [
    'graph_url' => env(
        'META_GRAPH_URL',
        'https://graph.facebook.com'
    ),

    'graph_version' => env(
        'META_GRAPH_VERSION',
        'v26.0'
    ),

    'timeout' => (int) env(
        'META_HTTP_TIMEOUT',
        15
    ),
    /*
     * Secret of OUR Meta Developer App.
     *
     * Used to authenticate webhook POST requests.
     */
    'app_secret' => env(
        'META_APP_SECRET'
    ),

    /*
     * This is NOT supplied by Meta.
     *
     * We generate it ourselves and enter the same
     * value in Meta's webhook configuration.
     */
    'webhook_verify_token' => env(
        'META_WEBHOOK_VERIFY_TOKEN'
    ),
],

];
