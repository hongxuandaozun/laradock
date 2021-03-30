<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Shop\Customers\Customer::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'micro' => [
        'api_gateway' => env('MICRO_API_GATEWAY'),
        'timeout' => env('MICRO_TIMEOUT'),
        'jwt_key' => env('MICRO_JWT_KEY'),
        'jwt_algorithms' => env('MICRO_JWT_ALGORITHMS'),
        'broker_host' => env('MICRO_BROKER_HOST'),
        'broker_port' => env('MICRO_BROKER_PORT')
    ]
];
