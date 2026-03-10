<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Money\Money;

interface BuyableInterface
{
    public function getCartItemIdentifier(): int|string;

    public function getCartItemName(): string;

    public function getPrice(): Money;

    /**
     * @return array<string, mixed>
     */
    public function getCartItemOptions(): array;
}
