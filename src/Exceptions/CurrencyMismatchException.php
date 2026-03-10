<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Exceptions;

class CurrencyMismatchException extends CartException
{
    public static function create(string $expected, string $actual, string $context = 'cart'): self
    {
        return new self("Currency mismatch in {$context}: expected {$expected}, got {$actual}.");
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: {$currency}.");
    }
}
