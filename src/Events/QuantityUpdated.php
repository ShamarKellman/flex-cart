<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use ShamarKellman\FlexCart\Contracts\CartItemInterface;

class QuantityUpdated extends CartEvent
{
    public function __construct(
        CartItemInterface $cartItem,
        int $oldQuantity,
        int $newQuantity
    ) {
        parent::__construct($cartItem, $oldQuantity, $newQuantity);
    }

    public function getOldQuantity(): int
    {
        return $this->oldQuantity;
    }

    public function getNewQuantity(): int
    {
        return $this->newQuantity ?? 0;
    }

    public function isIncrease(): bool
    {
        return $this->getNewQuantity() > $this->getOldQuantity();
    }

    public function isDecrease(): bool
    {
        return $this->getNewQuantity() < $this->getOldQuantity();
    }
}
