<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ShamarKellman\FlexCart\Contracts\BuyableInterface;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;
use ShamarKellman\FlexCart\Events\ItemAddedToCart;
use ShamarKellman\FlexCart\Events\ItemRemovedFromCart;
use ShamarKellman\FlexCart\Events\QuantityUpdated;
use ShamarKellman\FlexCart\Exceptions\CartItemNotFoundException;
use ShamarKellman\FlexCart\Exceptions\CurrencyMismatchException;
use ShamarKellman\FlexCart\Exceptions\InvalidQuantityException;
use ShamarKellman\FlexCart\Exceptions\ProductNotBuyableException;
use ShamarKellman\FlexCart\Models\Cart;
use ShamarKellman\FlexCart\Models\CartItem;
use ShamarKellman\FlexCart\Money\Calculator;
use ShamarKellman\FlexCart\Storage\DatabaseStorage;

/**
 * @property Collection<int, CartItemInterface> $items
 */
trait HasItems
{
    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidQuantityException
     * @throws ProductNotBuyableException
     * @throws CurrencyMismatchException
     */
    public function addItem(Model $buyable, int $quantity = 1, array $options = []): CartItemInterface
    {
        if (! $buyable instanceof BuyableInterface) {
            throw ProductNotBuyableException::fromModel($buyable);
        }

        if ($quantity < 1) {
            throw InvalidQuantityException::tooLow($quantity);
        }

        $existingItem = $this->findItemByBuyable($buyable, $options);

        if ($existingItem) {
            /** @var CartItem $existingItem */
            $oldQuantity = $existingItem->quantity;
            $existingItem->quantity += $quantity;
            $this->updateItemTotals($existingItem);
            $this->save();

            // Dispatch quantity updated event
            event(new QuantityUpdated($existingItem, $oldQuantity, $existingItem->getQuantity()));

            return $existingItem;
        }

        /**
         * @phpstan-ignore-next-line
         */
        $unitPrice = $this->getBuyablePrice($buyable);
        $item = new CartItem([
            'buyable_id' => $buyable->getKey(),
            'buyable_type' => get_class($buyable),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'options' => $options,
        ]);

        $this->updateItemTotals($item);

        // Ensure we have an ID for the item if using database storage
        if ($this->storage instanceof DatabaseStorage) {
            $this->cart ??= new Cart;
            if (! $this->cart->exists) {
                $this->save();
                $data = $this->storage->get($this->sessionKey);
                if (isset($data['cart']['id'])) {
                    $this->cart->id = $data['cart']['id'];
                    $this->cart->exists = true;
                }

            }
            $item->cart_id = $this->cart->id;
            $item->save();
        }

        // Assign a stable integer ID for session-storage items (DB items already have one from save())
        if (! $item->id) {
            $item->id = random_int(1, PHP_INT_MAX);
        }

        $this->items->add($item);

        $this->save();

        // Dispatch item added event
        event(new ItemAddedToCart($item, $quantity));

        return $item;
    }

    /**
     * @throws CartItemNotFoundException
     */
    public function updateItem(int $itemId, int $quantity): bool
    {
        $item = $this->findItem($itemId);

        if (! $item) {
            throw CartItemNotFoundException::withId($itemId);
        }

        if ($quantity < 1) {
            return $this->removeItem($itemId);
        }

        /** @var CartItem $item */
        $oldQuantity = $item->getQuantity();
        $item->setQuantity($quantity);
        $this->updateItemTotals($item);
        $this->save();

        // Dispatch quantity updated event
        event(new QuantityUpdated($item, $oldQuantity, $quantity));

        return true;
    }

    /**
     * Remove an item from the cart
     */
    public function removeItem(int $itemId): bool
    {
        $index = $this->findItemIndex($itemId);

        if ($index !== false) {
            $removedItem = $this->items->get($index);
            $this->items->forget([$index]);
            $this->save();

            // Dispatch item removed event
            if ($removedItem) {
                event(new ItemRemovedFromCart($removedItem, $removedItem->getQuantity()));
            }

            return true;
        }

        return false;
    }

    /**
     * @return Collection<int, CartItemInterface>
     */
    public function getItems(): Collection
    {
        return $this->items();
    }

    /**
     * @return Collection<int, CartItemInterface>
     */
    public function items(): Collection
    {
        return clone $this->items;
    }

    /**
     * Get a cart item by its ID
     */
    public function getItem(int $itemId): ?CartItemInterface
    {
        return $this->findItem($itemId);
    }

    /**
     * Prepare cart items data for storage
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prepareItemsData(): array
    {
        return $this->items->map(function (CartItem $item) {
            $data = $item->toArray();
            $data['unit_price'] = (int) $item->unit_price->getAmount();
            $data['total_price'] = (int) $item->total_price->getAmount();
            $data['tax_amount'] = (int) $item->tax_amount->getAmount();

            return $data;
        })->toArray();
    }

    /**
     * Find a cart item by its ID
     */
    protected function findItem(int $itemId): ?CartItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->id == $itemId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Find the index of a cart item by its ID
     */
    protected function findItemIndex(int $itemId): int|false
    {
        foreach ($this->items as $index => $item) {
            if ($item->id == $itemId) {
                return (int) $index;
            }
        }

        return false;
    }

    /**
     * Find an item by its buyable model and options
     *
     * @param  array<string, mixed>  $options
     */
    protected function findItemByBuyable(Model $buyable, array $options = []): ?CartItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->buyable_id == $buyable->getKey() &&
                $item->buyable_type == get_class($buyable) &&
                $item->options == $options) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Update cart item totals (total price and tax amount)
     */
    protected function updateItemTotals(CartItem $item): void
    {
        $total = Calculator::multiply($item->unit_price, $item->quantity);

        // Calculate tax-inclusive amounts
        $taxCalculation = Calculator::getTaxInclusiveAmount($total, $this->taxRate);

        $item->total_price = $total;
        $item->tax_amount = $taxCalculation['tax'];
    }
}
