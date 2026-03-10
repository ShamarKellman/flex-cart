<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;

class CartCleared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected Collection $clearedItems
    ) {}

    public function getClearedItems(): Collection
    {
        return $this->clearedItems;
    }

    public function getClearedItemCount(): int
    {
        return $this->clearedItems->count();
    }

    public function getClearedTotalQuantity(): int
    {
        return $this->clearedItems->sum(fn ($item) => $item->getQuantity());
    }

    public function getClearedSubtotal(): Money
    {
        if ($this->clearedItems->isEmpty()) {
            return new Money(0, new Currency(config('flex-cart.default_currency', 'USD')));
        }

        /** @var Money $subTotal */
        $subTotal = $this->clearedItems->reduce(
            fn (Money $carry, CartItemInterface $item): Money => $carry->add($item->getTotal()),
            new Money(0, new Currency(config()->string('flex-cart.default_currency')))
        );

        return $subTotal;
    }

    public function isEmpty(): bool
    {
        return $this->clearedItems->isEmpty();
    }
}
