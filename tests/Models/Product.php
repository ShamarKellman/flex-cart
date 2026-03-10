<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\BuyableInterface;

class Product extends Model implements BuyableInterface
{
    protected $guarded = [];

    public function getCartItemIdentifier(): int|string
    {
        return $this->id;
    }

    public function getCartItemName(): string
    {
        return $this->name;
    }

    public function getPrice(): Money
    {
        return new Money($this->price, new Currency(config('flex-cart.default_currency', 'USD')));
    }

    public function getCartItemOptions(): array
    {
        return [];
    }
}
