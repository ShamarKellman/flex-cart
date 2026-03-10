<?php

declare(strict_types=1);

return [
    'default_currency' => env('CART_DEFAULT_CURRENCY', 'USD'),

    'tax_rate' => env('CART_TAX_RATE', 0.0),

    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'session'),

        'session_key' => 'shopping_cart',

        'drivers' => [
            'session' => \ShamarKellman\FlexCart\Storage\SessionStorage::class,
            'database' => \ShamarKellman\FlexCart\Storage\DatabaseStorage::class,
        ],
    ],

    'models' => [
        'cart' => \ShamarKellman\FlexCart\Models\Cart::class,
        'cart_item' => \ShamarKellman\FlexCart\Models\CartItem::class,
        'shipping_detail' => \ShamarKellman\FlexCart\Models\ShippingDetail::class,
    ],
];
