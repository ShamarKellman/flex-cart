<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Money\Money;
use ShamarKellman\FlexCart\Models\ShippingDetail;

/**
 * @template TModel of Model
 */
interface CartInterface
{
    /**
     * @param  TModel  $buyable
     * @param  array<string, mixed>  $options
     */
    public function addItem(Model $buyable, int $quantity = 1, array $options = []): CartItemInterface;

    public function getItem(int $itemId): ?CartItemInterface;

    public function updateItem(int $itemId, int $quantity): bool;

    public function removeItem(int $itemId): bool;

    public function clear(): void;

    /**
     * @return Collection<int, CartItemInterface>
     */
    public function items(): Collection;

    public function total(): Money;

    public function subtotal(): Money;

    public function tax(): Money;

    public function shippingCost(): Money;

    public function count(): int;

    public function isEmpty(): bool;

    /**
     * @param  array  $address<string,  mixed>
     */
    public function setShippingAddress(array $address): void;

    public function setShippingCost(Money $cost): void;

    public function getShippingDetail(): ?ShippingDetail;

    public function addCoupon(CouponInterface $coupon): bool;

    public function removeCoupon(string $code): bool;

    public function hasCoupon(string $code): bool;

    public function getCoupon(string $code): ?CouponInterface;

    /**
     * @return Collection<string, CouponInterface>
     */
    public function getCoupons(): Collection;

    public function clearCoupons(): void;

    public function couponDiscount(): Money;

    public function subtotalWithCoupons(): Money;
}
