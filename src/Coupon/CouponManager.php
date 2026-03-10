<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Coupon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CouponInterface;
use ShamarKellman\FlexCart\Contracts\CouponManagerInterface;
use ShamarKellman\FlexCart\Events\CouponApplied;
use ShamarKellman\FlexCart\Events\CouponExpired;
use ShamarKellman\FlexCart\Events\CouponRemoved;

/**
 * @template TModel of Model
 * @template TCoupon of CouponInterface
 */
class CouponManager implements CouponManagerInterface
{
    /**
     * @var Collection<string, CouponInterface>
     */
    protected Collection $coupons;

    public function __construct()
    {
        $this->coupons = collect();
    }

    public function addCoupon(Model|CouponInterface $coupon): bool
    {
        $code = $coupon->getCode();

        if ($this->hasCoupon($code)) {
            return false;
        }

        // Check if coupon is expired before adding
        if ($coupon->isExpired()) {
            event(new CouponExpired($coupon));

            return false;
        }

        // Check stackability - if new coupon is not stackable and there are existing coupons, prevent adding
        if (! $coupon->isStackable() && $this->coupons->isNotEmpty()) {
            return false;
        }

        // Check if existing coupons are not stackable and new coupon is being added
        if ($this->coupons->isNotEmpty() && ! $this->coupons->first()->isStackable()) {
            return false;
        }

        $this->coupons->put($code, $coupon);

        event(new CouponApplied($coupon));

        return true;
    }

    public function removeCoupon(string $code): bool
    {
        if (! $this->hasCoupon($code)) {
            return false;
        }

        $coupon = $this->coupons->get($code);
        $this->coupons->forget($code);

        event(new CouponRemoved($coupon));

        return true;
    }

    public function hasCoupon(string $code): bool
    {
        return $this->coupons->has($code);
    }

    public function getCoupon(string $code): ?CouponInterface
    {
        return $this->coupons->get($code);
    }

    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function clearCoupons(): void
    {
        $this->coupons->each(function (CouponInterface $coupon) {
            event(new CouponRemoved($coupon));
        });

        $this->coupons = collect();
    }

    public function calculateTotalDiscount(Money $subtotal, Collection $items): Money
    {
        $discounts = $this->calculateSeparatedDiscounts($subtotal, $items);

        return $discounts['total'];
    }

    /**
     * @return array{general: Money, shipping: Money, total: Money}
     */
    public function calculateSeparatedDiscounts(Money $subtotal, Collection $items): array
    {
        $currency = $subtotal->getCurrency();
        $generalDiscount = new Money(0, $currency);
        $shippingDiscount = new Money(0, $currency);

        foreach ($this->getApplicableCoupons($subtotal, $items) as $coupon) {
            $discount = $coupon->calculateDiscount($subtotal, $items);
            if ($coupon->getType() === 'shipping') {
                $shippingDiscount = $shippingDiscount->add($discount);
            } else {
                $generalDiscount = $generalDiscount->add($discount);
            }
        }

        return [
            'general' => $generalDiscount,
            'shipping' => $shippingDiscount,
            'total' => $generalDiscount->add($shippingDiscount),
        ];
    }

    public function getApplicableCoupons(Money $subtotal, Collection $items): Collection
    {
        // Iterate over a snapshot so validateCoupon() can safely mutate $this->coupons (e.g. remove expired)
        return collect($this->coupons->all())->filter(function (CouponInterface $coupon) use ($subtotal, $items) {
            return $this->validateCoupon($coupon, $subtotal, $items);
        });
    }

    public function validateAllCoupons(Money $subtotal, Collection $items): bool
    {
        foreach ($this->coupons as $coupon) {
            if (! $this->validateCoupon($coupon, $subtotal, $items)) {
                return false;
            }
        }

        return true;
    }

    public function getTotalWithCoupons(Money $subtotal, Collection $items): Money
    {
        $totalDiscount = $this->calculateTotalDiscount($subtotal, $items);

        return $subtotal->subtract($totalDiscount);
    }

    protected function validateCoupon(CouponInterface $coupon, Money $subtotal, Collection $items): bool
    {
        if (! $coupon->isValid()) {
            if ($coupon->isExpired()) {
                event(new CouponExpired($coupon));
                $this->removeCoupon($coupon->getCode());
            }

            return false;
        }

        return $coupon->canBeApplied($subtotal, $items);
    }
}
