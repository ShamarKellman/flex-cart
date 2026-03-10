<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use ShamarKellman\FlexCart\Contracts\CartItemInterface;

class ItemRemovedFromCart extends CartEvent
{
    public function __construct(
        CartItemInterface $cartItem,
        int $oldQuantity
    ) {
        parent::__construct($cartItem, $oldQuantity, 0);
    }

    public function getRemovedQuantity(): int
    {
        return $this->getOldQuantity();
    }
}
