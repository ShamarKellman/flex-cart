<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;

/**
 * @template TModel of Model
 */
abstract class CartEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected CartItemInterface $cartItem,
        protected int $oldQuantity = 0,
        protected ?int $newQuantity = null
    ) {}

    public function getCartItem(): CartItemInterface
    {
        return $this->cartItem;
    }

    public function getOldQuantity(): int
    {
        return $this->oldQuantity;
    }

    public function getNewQuantity(): ?int
    {
        return $this->newQuantity;
    }

    public function getQuantityChange(): int
    {
        if ($this->newQuantity === null) {
            return 0;
        }

        return $this->newQuantity - $this->oldQuantity;
    }

    /**
     * @return TModel
     */
    public function getBuyable(): Model
    {
        return $this->cartItem->getBuyable();
    }

    public function getUnitPrice(): Money
    {
        return $this->cartItem->getUnitPrice();
    }

    public function getTotalPrice(): Money
    {
        return $this->cartItem->getTotal();
    }

    public function getTotal(): Money
    {
        return $this->cartItem->getTotal();
    }

    public function getTaxAmount(): Money
    {
        return $this->cartItem->getTaxAmount();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->cartItem->getOptions();
    }

    /**
     * @return list<string>
     */
    public function __sleep(): array
    {
        return ['cartItem', 'oldQuantity', 'newQuantity'];
    }
}
