<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Exceptions;

class InvalidQuantityException extends CartException
{
    public static function tooLow(int $quantity): self
    {
        return new self("Quantity must be at least 1, {$quantity} given.");
    }

    public static function invalid(mixed $quantity): self
    {
        $type = gettype($quantity);

        return new self("Quantity must be an integer, {$type} given.");
    }

    public static function exceedsLimit(int $quantity, int $limit): self
    {
        return new self("Quantity {$quantity} exceeds maximum limit of {$limit}.");
    }
}
