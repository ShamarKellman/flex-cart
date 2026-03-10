<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Storage;

use Illuminate\Support\Facades\DB;
use ShamarKellman\FlexCart\Contracts\CartStorageInterface;
use ShamarKellman\FlexCart\Models\Cart;
use ShamarKellman\FlexCart\Models\Coupon;

class DatabaseStorage implements CartStorageInterface
{
    public function get(string $key): ?array
    {
        $cart = Cart::where('session_id', $key)->first();

        if (! $cart) {
            return null;
        }

        $couponCodes = $cart->applied_coupons ?? [];
        $coupons = $couponCodes
            ? Coupon::whereIn('code', $couponCodes)->get()->toArray()
            : [];

        return [
            'cart' => $cart->toArray(),
            'items' => $cart->items->toArray(),
            'shipping' => $cart->shippingDetail?->toArray(),
            'coupons' => $coupons,
        ];
    }

    public function put(string $key, array $value): void
    {
        $couponCodes = array_column($value['coupons'] ?? [], 'code');

        $cart = Cart::updateOrCreate(
            ['session_id' => $key],
            array_merge($value['cart'], ['applied_coupons' => $couponCodes])
        );

        // Update items atomically
        DB::transaction(function () use ($cart, $value) {
            $cart->items()->delete();
            foreach ($value['items'] as $itemData) {
                $cart->items()->create($itemData);
            }
        });

        // Update shipping
        if (isset($value['shipping'])) {
            $cart->shippingDetail()->updateOrCreate(
                ['cart_id' => $cart->id],
                $value['shipping']
            );
        } else {
            $cart->shippingDetail()->delete();
        }
    }

    public function forget(string $key): void
    {
        Cart::query()->where('session_id', $key)->delete();
    }

    public function has(string $key): bool
    {
        return Cart::query()->where('session_id', $key)->exists();
    }
}
