<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Traits;

use Illuminate\Support\Collection;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CouponInterface;

trait HasCoupons
{
    /**
     * Add a coupon to the cart
     */
    public function addCoupon(CouponInterface $coupon): bool
    {
        return $this->couponManager->addCoupon($coupon);
    }

    /**
     * Remove a coupon from the cart
     */
    public function removeCoupon(string $code): bool
    {
        return $this->couponManager->removeCoupon($code);
    }

    /**
     * Check if the cart has a coupon
     */
    public function hasCoupon(string $code): bool
    {
        return $this->couponManager->hasCoupon($code);
    }

    /**
     * Get a coupon by its code
     */
    public function getCoupon(string $code): ?CouponInterface
    {
        return $this->couponManager->getCoupon($code);
    }

    /**
     * Get all coupons
     *
     * @return Collection<string, CouponInterface>
     */
    public function getCoupons(): Collection
    {
        return $this->couponManager->getCoupons();
    }

    /**
     * Clear all coupons from the cart
     */
    public function clearCoupons(): void
    {
        $this->couponManager->clearCoupons();
    }

    /**
     * Get the coupon discount (alias for generalDiscount)
     */
    public function couponDiscount(): Money
    {
        return $this->generalDiscount();
    }

    /**
     * Calculate the general discount from coupons
     */
    public function generalDiscount(): Money
    {
        $subtotal = $this->subtotal();

        return $this->couponManager->calculateSeparatedDiscounts($subtotal, $this->items)['general'];
    }

    /**
     * Calculate the shipping discount from coupons
     */
    public function shippingDiscount(): Money
    {
        $subtotal = $this->subtotal();

        return $this->couponManager->calculateSeparatedDiscounts($subtotal, $this->items)['shipping'];
    }

    /**
     * Calculate the total discount from all coupons
     */
    public function totalDiscount(): Money
    {
        $subtotal = $this->subtotal();

        return $this->couponManager->calculateTotalDiscount($subtotal, $this->items);
    }

    /**
     * Calculate subtotal after applying coupons
     */
    public function subtotalWithCoupons(): Money
    {
        $subtotal = $this->subtotal();
        $discount = $this->generalDiscount();

        return $subtotal->subtract($discount);
    }

    /**
     * Prepare coupons data for storage
     *
     * @return array<string, array<string, mixed>>
     */
    protected function prepareCouponsData(): array
    {
        return $this->couponManager->getCoupons()->map(function (CouponInterface $coupon) {
            return $coupon->toArray();
        })->toArray();
    }
}
