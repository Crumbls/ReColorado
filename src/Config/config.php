<?php

return [
    'login_url' => env('RECOLORADO_LOGIN_URL'),
    'username' => env('RECOLORADO_USERNAME'),
    'password' => env('RECOLORADO_PASSWORD'),

    'model_map' => [
        'Office' => \Crumbls\Egent\Core\Models\Brokerage::class,
        'Member' => \Crumbls\Egent\Core\Models\User::class,
        'Property' => \Crumbls\Egent\Core\Models\Property::class
    ],

    'model_key' => [
        'Member' => 'member_email',
        'Office' => 'mls_id',
        'Property' => 'listing_id'
    ]
];