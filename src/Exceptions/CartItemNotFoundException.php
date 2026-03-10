<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Exceptions;

class CartItemNotFoundException extends CartException
{
    public static function withId(string|int $itemId): self
    {
        return new self("Cart item with ID '{$itemId}' not found.");
    }

    public static function withBuyable(string|int $buyableId, string $buyableType): self
    {
        return new self("Cart item for buyable {$buyableType}:{$buyableId} not found.");
    }
}
