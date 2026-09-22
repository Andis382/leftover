<?php

/*
 * Everything product-specific the app reads from the environment, in one place.
 * Defaults are safe for local development; production sets the secrets.
 */
return [
    'name' => env('APP_NAME', 'Leftover'),

    // Base URL of the web app, used to build links sent to customers.
    'public_url' => rtrim(env('APP_PUBLIC_URL', 'http://localhost:5173'), '/'),

    // Demo mode seeds a sample organisation and enables the message simulator.
    'demo' => (bool) env('APP_DEMO', true),

    'default_locale' => env('APP_DEFAULT_LOCALE', 'sq'),
    'default_timezone' => env('APP_DEFAULT_TIMEZONE', 'Europe/Tirane'),
    'default_country_code' => env('APP_DEFAULT_COUNTRY_CODE', '355'),

    'demo_email' => 'demo@leftover.test',
    'demo_password' => 'demo1234',

    'messaging' => [
        // "log" keeps messages in the outbox only; "whatsapp" sends through the Cloud API.
        'driver' => env('MESSAGING_DRIVER', 'log'),
        // App\Messaging\InboundHandler classes, tried in order for each customer message.
        'inbound_handlers' => [],
        'whatsapp' => [
            'token' => env('WHATSAPP_TOKEN', ''),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
            'app_secret' => env('WHATSAPP_APP_SECRET', ''),
            'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),
            'api_version' => env('WHATSAPP_API_VERSION', 'v23.0'),
            // template key => "approved_template_name|param1,param2"
            'templates' => [],
        ],
    ],

    'ai' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
    ],
];
