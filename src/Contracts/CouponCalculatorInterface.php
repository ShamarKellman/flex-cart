<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Support\Collection;
use Money\Money;

interface CouponCalculatorInterface
{
    /**
     * @param  Collection<int, CartItemInterface>  $items
     */
    public function calculateDiscount(CouponInterface $coupon, Money $subtotal, Collection $items): Money;

    /**
     * @param  Collection<int, CartItemInterface>  $items
     */
    public function validateCoupon(CouponInterface $coupon, Money $subtotal, Collection $items): bool;

    /**
     * @param  Collection<int, CartItemInterface>  $items
     * @return Collection<int, CartItemInterface>
     */
    public function getApplicableItems(CouponInterface $coupon, Collection $items): Collection;

    /**
     * @param  Collection<int, CartItemInterface>  $items
     */
    public function calculateApplicableSubtotal(CouponInterface $coupon, Collection $items): Money;
}
