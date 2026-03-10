<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Events\CartCleared;
use ShamarKellman\FlexCart\Events\ItemAddedToCart;
use ShamarKellman\FlexCart\Events\ItemRemovedFromCart;
use ShamarKellman\FlexCart\Events\QuantityUpdated;
use ShamarKellman\FlexCart\Events\ShippingAddressSet;
use ShamarKellman\FlexCart\Events\ShippingCostSet;
use ShamarKellman\FlexCart\Facades\FlexCart;

beforeEach(function () {
    config()->set('flex-cart.storage.driver', 'database');
    config()->set('flex-cart.storage.drivers.database', \ShamarKellman\FlexCart\Storage\DatabaseStorage::class);
    config()->set('flex-cart.tax_rate', 0.0);

    $this->product = \ShamarKellman\FlexCart\Tests\Models\Product::create([
        'name' => 'Test Product',
        'price' => 1000, // $10.00
    ]);
});

afterEach(function () {
    FlexCart::clear();
});

it('dispatches ItemAddedToCart event when adding new item', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 2);

    Event::assertDispatched(ItemAddedToCart::class, function ($event) use ($cartItem) {
        return $event->getCartItem()->id === $cartItem->id &&
               $event->getAddedQuantity() === 2 &&
               $event->getQuantityChange() === 2;
    });
});

it('dispatches QuantityUpdated event when adding to existing item', function () {
    Event::fake();

    // Add initial item
    $cartItem = FlexCart::addItem($this->product, 1);
    Event::assertDispatched(ItemAddedToCart::class, 1);

    // Clear events for this test
    Event::fake();

    // Add more to the same item
    FlexCart::addItem($this->product, 2);

    Event::assertDispatched(QuantityUpdated::class, function ($event) {
        return $event->getOldQuantity() === 1 &&
               $event->getNewQuantity() === 3 &&
               $event->isIncrease() &&
               ! $event->isDecrease() &&
               $event->getQuantityChange() === 2;
    });
});

it('dispatches ItemRemovedFromCart event when removing item', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 3);
    $itemId = $cartItem->id;

    Event::fake(); // Clear previous events

    FlexCart::removeItem($itemId);

    Event::assertDispatched(ItemRemovedFromCart::class, function ($event) use ($cartItem) {
        return $event->getCartItem()->id === $cartItem->id &&
               $event->getRemovedQuantity() === 3;
    });
});

it('dispatches QuantityUpdated event when updating item quantity', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 1);
    $itemId = $cartItem->id;

    Event::fake(); // Clear previous events

    FlexCart::updateItem($itemId, 5);

    Event::assertDispatched(QuantityUpdated::class, function ($event) {
        return $event->getOldQuantity() === 1 &&
               $event->getNewQuantity() === 5 &&
               $event->isIncrease() &&
               $event->getQuantityChange() === 4;
    });
});

it('dispatches QuantityUpdated event when decreasing quantity', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 5);
    $itemId = $cartItem->id;

    Event::fake(); // Clear previous events

    FlexCart::updateItem($itemId, 2);

    Event::assertDispatched(QuantityUpdated::class, function ($event) {
        return $event->getOldQuantity() === 5 &&
               $event->getNewQuantity() === 2 &&
               $event->isDecrease() &&
               ! $event->isIncrease() &&
               $event->getQuantityChange() === -3;
    });
});

it('dispatches CartCleared event when clearing cart', function () {
    Event::fake();

    $item1 = FlexCart::addItem($this->product, 1);
    $item2 = FlexCart::additem(\ShamarKellman\FlexCart\Tests\Models\Product::create(['name' => 'Test 2', 'price' => 2000]), 2);

    Event::fake(); // Clear previous events

    FlexCart::clear();

    Event::assertDispatched(CartCleared::class, function ($event) {
        return $event->getClearedItemCount() === 2 &&
               $event->getClearedTotalQuantity() === 3 &&
               ! $event->isEmpty();
    });
});

it('dispatches CartCleared event with empty cart', function () {
    Event::fake();

    FlexCart::clear();

    Event::assertDispatched(CartCleared::class, function ($event) {
        return $event->getClearedItemCount() === 0 &&
               $event->getClearedTotalQuantity() === 0 &&
               $event->isEmpty();
    });
});

it('dispatches ShippingCostSet event when setting shipping cost', function () {
    Event::fake();

    FlexCart::addItem($this->product, 1);

    Event::fake(); // Clear previous events

    $oldCost = FlexCart::shippingCost();
    $newCost = new Money(1500, new Currency('USD'));

    FlexCart::setShippingCost($newCost);

    Event::assertDispatched(ShippingCostSet::class, function ($event) use ($oldCost, $newCost) {
        return $event->getOldCost()->getAmount() === $oldCost->getAmount() &&
               $event->getNewCost()->getAmount() === $newCost->getAmount() &&
               $event->hasIncreased() &&
               ! $event->hasDecreased() &&
               ! $event->isZero();
    });
});

it('dispatches ShippingCostSet event when decreasing shipping cost', function () {
    Event::fake();

    FlexCart::addItem($this->product, 1);
    FlexCart::setShippingCost(new Money(2000, new Currency('USD')));

    Event::fake(); // Clear previous events

    $newCost = new Money(500, new Currency('USD'));

    FlexCart::setShippingCost($newCost);

    Event::assertDispatched(ShippingCostSet::class, function ($event) use ($newCost) {
        return $event->getNewCost()->getAmount() === $newCost->getAmount() &&
               $event->hasDecreased() &&
               ! $event->hasIncreased();
    });
});

it('dispatches ShippingAddressSet event when setting shipping address', function () {
    Event::fake();

    FlexCart::addItem($this->product, 1);

    Event::fake(); // Clear previous events

    $address = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country' => 'US',
    ];

    FlexCart::setShippingAddress($address);

    Event::assertDispatched(ShippingAddressSet::class, function ($event) {
        return $event->isFirstAddress() &&
               $event->getCountry() === 'US' &&
               $event->getPostalCode() === '10001';
    });
});

it('dispatches ShippingAddressSet event when changing shipping address', function () {
    Event::fake();

    FlexCart::addItem($this->product, 1);

    // Set initial address
    FlexCart::setShippingAddress([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country' => 'US',
    ]);

    Event::fake(); // Clear previous events

    $newAddress = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country' => 'US',
    ];

    FlexCart::setShippingAddress($newAddress);

    Event::assertDispatched(ShippingAddressSet::class, function ($event) {
        return ! $event->isFirstAddress() &&
               $event->hasChanged() &&
               $event->getCountry() === 'US';
    });
});

it('provides access to cart item data in events', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 2, ['color' => 'blue', 'size' => 'large']);

    Event::assertDispatched(ItemAddedToCart::class, function ($event) {
        return $event->getBuyable()->id === $this->product->id &&
               $event->getUnitPrice()->getAmount() === '1000' &&
               $event->getTotal()->getAmount() === '2000' &&
               $event->getOptions() === ['color' => 'blue', 'size' => 'large'];
    });
});

it('supports multiple event listeners', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 2);

    // Should dispatch ItemAddedToCart when first added
    Event::assertDispatched(ItemAddedToCart::class, 1);

    // Adding more should dispatch QuantityUpdated
    FlexCart::addItem($this->product, 1);

    Event::assertDispatched(QuantityUpdated::class, 1);

    // Total events should be 2 (1 add, 1 update)
    $dispatchedEvents = Event::dispatched(ItemAddedToCart::class);
    expect($dispatchedEvents)->toHaveCount(1);
});

it('event data contains expected information', function () {
    Event::fake();

    $cartItem = FlexCart::addItem($this->product, 2, ['color' => 'blue', 'size' => 'large']);

    Event::assertDispatched(ItemAddedToCart::class, function ($event) use ($cartItem) {
        return $event->getCartItem()->getBuyable()->id === $cartItem->getBuyable()->id &&
               $event->getCartItem()->getUnitPrice()->getAmount() === '1000' &&
               $event->getCartItem()->getTotal()->getAmount() === '2000' &&
               $event->getCartItem()->getOptions() === ['color' => 'blue', 'size' => 'large'];
    });
});
