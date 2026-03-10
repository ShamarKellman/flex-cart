<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Exceptions\CartItemNotFoundException;
use ShamarKellman\FlexCart\Exceptions\CurrencyMismatchException;
use ShamarKellman\FlexCart\Exceptions\InvalidQuantityException;
use ShamarKellman\FlexCart\Exceptions\ProductNotBuyableException;
use ShamarKellman\FlexCart\Facades\FlexCart;
use ShamarKellman\FlexCart\Storage\DatabaseStorage;
use ShamarKellman\FlexCart\Tests\Models\Product;

beforeEach(function () {
    config()->set('flex-cart.storage.driver', 'database');
    config()->set('flex-cart.storage.drivers.database', DatabaseStorage::class);
    config()->set('flex-cart.tax_rate', 0.0);

    $this->product = Product::create([
        'name' => 'Test Product',
        'price' => 1000, // $10.00
    ]);
});

afterEach(function () {
    FlexCart::clear();
});

it('throws ProductNotBuyableException for non-buyable model', function () {
    $nonBuyable = new class extends Model {};

    expect(fn () => FlexCart::addItem($nonBuyable, 1))
        ->toThrow(ProductNotBuyableException::class, 'must implement BuyableInterface');
});

it('throws InvalidQuantityException for quantity too low', function () {
    expect(fn () => FlexCart::addItem($this->product, 0))
        ->toThrow(InvalidQuantityException::class, 'Quantity must be at least 1');
});

it('throws InvalidQuantityException for negative quantity', function () {
    expect(fn () => FlexCart::addItem($this->product, -1))
        ->toThrow(InvalidQuantityException::class, 'Quantity must be at least 1');
});

it('throws CartItemNotFoundException when updating non-existent item', function () {
    expect(fn () => FlexCart::updateItem(10000, 2))
        ->toThrow(CartItemNotFoundException::class, 'Cart item with ID \'10000\' not found');
});

it('provides helpful exception messages for non-buyable models', function () {
    $nonBuyable = new class extends Model {};

    expect(fn () => FlexCart::addItem($nonBuyable, 1))
        ->toThrow(ProductNotBuyableException::class, 'must implement BuyableInterface');
});

it('throws CurrencyMismatchException for mismatched currencies in shipping cost', function () {
    FlexCart::addItem($this->product, 1);

    expect(fn () => FlexCart::setShippingCost(new Money(1000, new Currency('EUR'))))
        ->toThrow(CurrencyMismatchException::class, 'Currency mismatch in shipping cost: expected USD, got EUR');
});
