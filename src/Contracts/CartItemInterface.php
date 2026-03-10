<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Database\Eloquent\Model;
use Money\Money;
use ShamarKellman\FlexCart\Models\CartItem;

/**
 * @template TModel of Model
 *
 * @phpstan-require-extends CartItem
 */
interface CartItemInterface
{
    /**
     * @return TModel
     */
    public function getBuyable(): Model;

    public function getQuantity(): int;

    public function getUnitPrice(): Money;

    public function getTotal(): Money;

    public function getTaxAmount(): Money;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    public function setQuantity(int $quantity): void;

    /**
     * @param  array<string, mixed>  $options
     */
    public function setOptions(array $options): void;
}
