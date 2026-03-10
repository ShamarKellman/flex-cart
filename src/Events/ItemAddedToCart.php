<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use ShamarKellman\FlexCart\Contracts\CartItemInterface;

class ItemAddedToCart extends CartEvent
{
    public function __construct(
        CartItemInterface $cartItem,
        int $quantity
    ) {
        parent::__construct($cartItem, 0, $quantity);
    }

    public function getAddedQuantity(): int
    {
        return $this->getNewQuantity() ?? 0;
    }
}
