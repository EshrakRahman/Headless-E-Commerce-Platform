<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Stripe secret key, used for all server-side API calls. This key
    | should never be exposed to the frontend or committed to version control.
    |
    */

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Publishable Key
    |--------------------------------------------------------------------------
    |
    | Your Stripe publishable key, used by Stripe.js on the frontend.
    | This key is safe to expose in client-side code.
    |
    */

    'key' => env('STRIPE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The signing secret for verifying Stripe webhook events. This ensures
    | that the webhook requests are genuinely coming from Stripe.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The ISO currency code used for all Stripe charges and payment intents.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'usd'),

];
