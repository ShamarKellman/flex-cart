<?php

declare(strict_types=1);
use ShamarKellman\FlexCart\Models\Cart;
use ShamarKellman\FlexCart\Models\CartItem;
use ShamarKellman\FlexCart\Models\ShippingDetail;
use ShamarKellman\FlexCart\Storage\DatabaseStorage;
use ShamarKellman\FlexCart\Storage\SessionStorage;

return [
    'default_currency' => env('CART_DEFAULT_CURRENCY', 'USD'),

    'tax_rate' => env('CART_TAX_RATE', 0.0),

    'storage' => [
        'driver' => env('CART_STORAGE_DRIVER', 'session'),

        'session_key' => 'shopping_cart',

        'drivers' => [
            'session' => SessionStorage::class,
            'database' => DatabaseStorage::class,
        ],
    ],

    'models' => [
        'cart' => Cart::class,
        'cart_item' => CartItem::class,
        'shipping_detail' => ShippingDetail::class,
    ],
];
