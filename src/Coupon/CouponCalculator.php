<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Coupon;

use Illuminate\Support\Collection;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;
use ShamarKellman\FlexCart\Contracts\CouponCalculatorInterface;
use ShamarKellman\FlexCart\Contracts\CouponInterface;
use ShamarKellman\FlexCart\Models\Coupon;

class CouponCalculator implements CouponCalculatorInterface
{
    public function calculateDiscount(CouponInterface $coupon, Money $subtotal, Collection $items): Money
    {
        if (! $this->validateCoupon($coupon, $subtotal, $items)) {
            return new Money(0, $subtotal->getCurrency());
        }

        return match ($coupon->getType()) {
            Coupon::TYPE_FIXED_AMOUNT => $this->calculateFixedAmountDiscount($coupon, $subtotal),
            Coupon::TYPE_PERCENTAGE => $this->calculatePercentageDiscount($coupon, $subtotal),
            Coupon::TYPE_SHIPPING => $this->calculateShippingDiscount($coupon),
            default => new Money(0, $subtotal->getCurrency()),
        };
    }

    public function validateCoupon(CouponInterface $coupon, Money $subtotal, Collection $items): bool
    {
        if (! $coupon->isValid()) {
            return false;
        }

        if ($coupon->getMinimumAmount() && $subtotal->lessThan($coupon->getMinimumAmount())) {
            return false;
        }

        $applicableItems = $this->getApplicableItems($coupon, $items);
        if ($applicableItems->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, CartItemInterface>  $items
     * @return Collection<int, CartItemInterface>
     */
    public function getApplicableItems(CouponInterface $coupon, Collection $items): Collection
    {
        if (! $coupon->getAppliesTo() && ! $coupon->getExcludes()) {
            return $items;
        }

        return $items->filter(function (CartItemInterface $item) use ($coupon) {
            $buyable = $item->getBuyable();
            $buyableId = $buyable->getKey();
            $buyableType = get_class($buyable);

            if ($coupon->getAppliesTo()) {
                return in_array($buyableId, $coupon->getAppliesTo()) ||
                       in_array($buyableType, $coupon->getAppliesTo());
            }

            if ($coupon->getExcludes()) {
                return ! in_array($buyableId, $coupon->getExcludes()) &&
                       ! in_array($buyableType, $coupon->getExcludes());
            }

            return true;
        });
    }

    /**
     * @param  Collection<int, CartItemInterface>  $items
     */
    public function calculateApplicableSubtotal(CouponInterface $coupon, Collection $items): Money
    {
        $applicableItems = $this->getApplicableItems($coupon, $items);

        $subtotal = new Money(0, new Currency(config()->string('flex-cart.default_currency')));

        foreach ($applicableItems as $item) {
            $subtotal = $subtotal->add($item->getTotal());
        }

        return $subtotal;
    }

    protected function calculateFixedAmountDiscount(CouponInterface $coupon, Money $subtotal): Money
    {
        $discountAmount = new Money((string) $coupon->getValue(), new Currency(config()->string('flex-cart.default_currency')));

        return $discountAmount->greaterThan($subtotal)
            ? $subtotal
            : $discountAmount;
    }

    protected function calculatePercentageDiscount(CouponInterface $coupon, Money $subtotal): Money
    {
        $percentage = $coupon->getValue();
        $multiplier = $percentage / 100;

        return $subtotal->multiply((string) $multiplier);
    }

    protected function calculateShippingDiscount(CouponInterface $coupon): Money
    {
        return new Money((string) $coupon->getValue(), new Currency(config()->string('flex-cart.default_currency')));
    }
}
