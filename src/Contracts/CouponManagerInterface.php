<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Support\Collection;
use Money\Money;

interface CouponManagerInterface
{
    public function addCoupon(CouponInterface $coupon): bool;

    public function removeCoupon(string $code): bool;

    public function hasCoupon(string $code): bool;

    public function getCoupon(string $code): ?CouponInterface;

    /**
     * @return Collection<string, CouponInterface>
     */
    public function getCoupons(): Collection;

    public function clearCoupons(): void;

    /**
     * @param  Collection<int, CouponInterface>  $items
     */
    public function calculateTotalDiscount(Money $subtotal, Collection $items): Money;

    /**
     * @param  Collection<string, CouponInterface>  $items
     * @return Collection<string, CouponInterface>
     */
    public function getApplicableCoupons(Money $subtotal, Collection $items): Collection;

    /**
     * @param  Collection<int, CouponInterface>  $items
     */
    public function validateAllCoupons(Money $subtotal, Collection $items): bool;

    /**
     * @param  Collection<int, CouponInterface>  $items
     */
    public function getTotalWithCoupons(Money $subtotal, Collection $items): Money;
}
