<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CouponInterface;

class CouponRemoved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected CouponInterface $coupon,
        protected ?Money $discountAmount = null
    ) {}

    public function getCoupon(): CouponInterface
    {
        return $this->coupon;
    }

    public function getDiscountAmount(): ?Money
    {
        return $this->discountAmount;
    }

    public function getCode(): string
    {
        return $this->coupon->getCode();
    }

    public function getType(): string
    {
        return $this->coupon->getType();
    }

    public function getValue(): Money|int
    {
        return $this->coupon->getValue();
    }

    public function __sleep(): array
    {
        return ['coupon', 'discountAmount'];
    }
}
