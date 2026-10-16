<?php

return [

    'rates' => [
        'base_fee' => env('SHIPPING_BASE_FEE', 200),            // PKR
        'per_kg' => env('SHIPPING_PER_KG', 150),                // PKR per kg
        'out_of_city_multiplier' => env('SHIPPING_OUT_OF_CITY_MULTIPLIER', 1.5),
    ],

    'courier' => [
        'driver' => env('COURIER_DRIVER', 'fake'),              // fake | http
        'fail_rate' => env('COURIER_FAIL_RATE', 0),             // 0 to 1, only for the fake driver
        'base_url' => env('COURIER_BASE_URL', 'https://api.example-courier.test'),
        'api_key' => env('COURIER_API_KEY', ''),
    ],

];
