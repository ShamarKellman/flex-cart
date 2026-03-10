<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Money\Money;

class ShippingCostSet
{
    use Dispatchable;

    public function __construct(
        protected Money $oldCost,
        protected Money $newCost
    ) {}

    public function getOldCost(): Money
    {
        return $this->oldCost;
    }

    public function getNewCost(): Money
    {
        return $this->newCost;
    }

    public function getDifference(): Money
    {
        return $this->newCost->subtract($this->oldCost);
    }

    public function hasIncreased(): bool
    {
        return $this->newCost->greaterThan($this->oldCost);
    }

    public function hasDecreased(): bool
    {
        return $this->newCost->lessThan($this->oldCost);
    }

    public function isZero(): bool
    {
        return $this->newCost->isZero();
    }
}
