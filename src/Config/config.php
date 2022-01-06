<?php

return [
    'login_url' => env('RECOLORADO_LOGIN_URL'),
    'username' => env('RECOLORADO_USERNAME'),
    'password' => env('RECOLORADO_PASSWORD'),

    'model_map' => [
        'Office' => \App\Models\Brokerage::class,
        'Member' => \App\Models\User::class,
    ],

    'model_key' => [
        'Property' => 'listing_id',
        'Member' => 'member_email',
    ]
];