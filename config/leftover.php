<?php

return [
    'locales' => [
        'sq' => 'Shqip',
        'en' => 'English',
    ],

    'defaults' => [
        'locale' => env('LEFTOVER_DEFAULT_LOCALE', 'sq'),
        'timezone' => env('LEFTOVER_DEFAULT_TIMEZONE', 'Europe/Tirane'),
        'currency' => env('LEFTOVER_CURRENCY', 'EUR'),
        'opens_at' => env('LEFTOVER_OPENS_AT', '06:00'),
        'closes_at' => env('LEFTOVER_CLOSES_AT', '20:00'),
        'plan_at' => env('LEFTOVER_PLAN_AT', '04:00'),
    ],

    'currency_symbols' => [
        'EUR' => '€',
        'ALL' => 'L',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF',
        'RSD' => 'din',
        'MKD' => 'ден',
    ],

    /*
    |---------------------------------------------------------------------------
    | How the plan reaches the baker
    |---------------------------------------------------------------------------
    | none     : the default. The plan is a page. Open it while the oven heats.
    |            Nothing is sent anywhere and no account is needed.
    | telegram : a bot pushes the plan at the hour you set. A bot token takes
    |            two minutes to create, costs nothing, and needs no approval of
    |            any message template by anyone.
    | log      : write it to the log. For demos and tests.
    |
    | WhatsApp is deliberately not a server-side driver. Sending a scheduled
    | message from a server through WhatsApp means a Meta Business account, a
    | verified number and pre-approved templates. Every plan screen offers a
    | one-tap link that opens the plan in your own WhatsApp instead, which costs
    | nothing and needs nobody's permission.
    */
    'notify' => [
        'driver' => env('LEFTOVER_NOTIFY_DRIVER', 'none'),
        'telegram_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
    |---------------------------------------------------------------------------
    | The forecast
    |---------------------------------------------------------------------------
    | These live in App\Services\Forecaster as constants, because they are part
    | of the promise the product makes rather than something to tune per install.
    | They are repeated here only so that they are findable.
    |
    |   history_weeks       8     how far back the same weekday is read
    |   min_observations    2     below this, the line stays silent
    |   max_move_fraction   0.20  a suggestion may never move production further
    |   max_sellout_uplift  0.30  the most an early sell-out may inflate demand
    */
];
