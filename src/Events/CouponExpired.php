<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CouponInterface;

class CouponExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected CouponInterface $coupon
    ) {}

    public function getCoupon(): CouponInterface
    {
        return $this->coupon;
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

    public function getUsageCount(): int
    {
        return $this->coupon->getUsedCount();
    }

    /**
     * @return list<string>
     */
    public function __sleep(): array
    {
        return ['coupon'];
    }
}
