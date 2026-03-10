<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Money\Currency;
use Money\Money;
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

it('can add an item to the cart', function () {
    FlexCart::addItem($this->product, 1);

    expect(FlexCart::count())->toBe(1)
        ->and(FlexCart::items())->toHaveCount(1)
        ->and(FlexCart::total()->getAmount())->toBe('1000');
});

it('can add multiple quantities of an item', function () {
    FlexCart::addItem($this->product, 2);

    expect(FlexCart::count())->toBe(2)
        ->and(FlexCart::total()->getAmount())->toBe('2000');
});

it('can update an item quantity', function () {
    $cartItem = FlexCart::addItem($this->product, 1);

    FlexCart::updateItem($cartItem->id, 3);

    expect(FlexCart::count())->toBe(3)
        ->and(FlexCart::total()->getAmount())->toBe('3000');
});

it('can remove an item from the cart', function () {
    $cartItem = FlexCart::addItem($this->product, 1);

    expect(FlexCart::count())->toBe(1);

    FlexCart::removeItem($cartItem->id);

    expect(FlexCart::count())->toBe(0);
});

it('can clear the cart', function () {
    FlexCart::addItem($this->product, 1);
    FlexCart::addItem(Product::create(['name' => 'P2', 'price' => 500]), 1);

    expect(FlexCart::count())->toBe(2);

    FlexCart::clear();

    expect(FlexCart::count())->toBe(0);
});

it('calculates subtotal, tax and total correctly', function () {
    config()->set('flex-cart.tax_rate', 10.0); // 10% tax

    // Total $11.00 inclusive of 10% tax
    // 1100 / 1.1 = 1000 (net)
    // 1100 - 1000 = 100 (tax)

    $product = Product::create(['name' => 'Taxed Product', 'price' => 1100]);
    FlexCart::addItem($product, 1);

    expect(FlexCart::total()->getAmount())->toBe('1100')
        ->and(FlexCart::subtotal()->getAmount())->toBe('1100')
        ->and(FlexCart::subtotalNet()->getAmount())->toBe('1000')
        ->and(FlexCart::tax()->getAmount())->toBe('100');
});

it('can set shipping cost', function () {
    FlexCart::addItem($this->product, 1);

    $shippingCost = new Money(500, new Currency('USD'));
    FlexCart::setShippingCost($shippingCost);

    expect(FlexCart::shippingCost()->getAmount())->toBe('500')
        ->and(FlexCart::total()->getAmount())->toBe('1500');
});

it('can set shipping address', function () {
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

    $detail = FlexCart::getShippingDetail();
    expect($detail->first_name)->toBe('John')
        ->and($detail->last_name)->toBe('Doe')
        ->and($detail->country)->toBe('US');

    expect($detail->first_name)->toBe('John')
        ->and($detail->last_name)->toBe('Doe')
        ->and($detail->address_line_1)->toBe('123 Main St')
        ->and($detail->city)->toBe('New York')
        ->and($detail->state)->toBe('NY')
        ->and($detail->postal_code)->toBe('10001')
        ->and($detail->country)->toBe('US')
        ->and($detail->cart())->toBeInstanceOf(BelongsTo::class);
});

it('can get cart from flexcart', function () {
    FlexCart::addItem($this->product, 1);
    expect(FlexCart::getCart())->toBeInstanceOf(\ShamarKellman\FlexCart\Models\Cart::class);
});

it('can set and get currency', function () {
    config()->set('flex-cart.default_currency', 'EUR');
    FlexCart::addItem($this->product, 1);

    expect(FlexCart::total()->getCurrency()->getCode())->toBe('EUR');
});

it('can handle numeric tax rate', function () {
    FlexCart::clear();
    session()->forget('shopping_cart');
    config()->set('flex-cart.tax_rate', 15.0);

    $cart = app(\ShamarKellman\FlexCart\FlexCart::class, [
        'storage' => app(\ShamarKellman\FlexCart\Storage\DatabaseStorage::class),
        'sessionKey' => session()->getId(),
    ]);

    // Total $11.50 -> subtotal $10.00, tax $1.50
    $product = Product::create(['name' => 'Taxed Product 2', 'price' => 1150]);
    $cart->addItem($product, 1);

    expect($cart->tax()->getAmount())->toBe('150');
});

it('can handle missing items in storage', function () {
    $storage = Mockery::mock(\ShamarKellman\FlexCart\Contracts\CartStorageInterface::class);
    $storage->shouldReceive('get')->andReturn(['cart' => []]); // no items key

    $cart = new \ShamarKellman\FlexCart\FlexCart($storage);
    expect($cart->items())->toHaveCount(0);
});

it('can handle shipping cost as money object in storage', function () {
    $cost = new Money(1000, new Currency('USD'));
    $storage = Mockery::mock(\ShamarKellman\FlexCart\Contracts\CartStorageInterface::class);
    $storage->shouldReceive('get')->andReturn([
        'cart' => ['shipping_cost' => $cost],
        'items' => [],
    ]);

    $cart = new \ShamarKellman\FlexCart\FlexCart($storage, 'test');
    expect($cart->shippingCost()->getAmount())->toBe('1000');
});

it('can handle shipping cost as array in storage', function () {
    $storage = Mockery::mock(\ShamarKellman\FlexCart\Contracts\CartStorageInterface::class);
    $storage->shouldReceive('get')->andReturn([
        'cart' => ['shipping_cost' => ['amount' => '2000', 'currency' => 'USD']],
        'items' => [],
    ]);

    $cart = new \ShamarKellman\FlexCart\FlexCart($storage, 'test');
    expect($cart->shippingCost()->getAmount())->toBe('2000');
});

it('money cast get returns money object', function () {
    $cast = new \ShamarKellman\FlexCart\Money\MoneyCast;
    $model = new \ShamarKellman\FlexCart\Models\CartItem;
    $money = $cast->get($model, 'unit_price', 1000, []);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('1000');
});

it('works with session storage', function () {
    config()->set('flex-cart.storage.driver', 'session');
    config()->set('flex-cart.storage.session_key', 'cart');

    FlexCart::addItem($this->product, 1);

    expect(FlexCart::count())->toBe(1);
    expect(session()->has('cart.'.session()->getId()))->toBeTrue();
});

it('can get cart item details', function () {
    $item = FlexCart::addItem($this->product, 2, ['color' => 'blue']);

    expect($item->getQuantity())->toBe(2)
        ->and($item->getUnitPrice()->getAmount())->toBe('1000')
        ->and($item->getTotal()->getAmount())->toBe('2000')
        ->and($item->getTaxAmount()->getAmount())->toBe('0')
        ->and($item->getOptions())->toBe(['color' => 'blue'])
        ->and($item->getBuyable()->id)->toBe($this->product->id)
        ->and($item->cart())->toBeInstanceOf(BelongsTo::class)
        ->and($item->buyable())->toBeInstanceOf(MorphTo::class);
});

it('can use money calculator', function () {
    $m1 = new Money(100, new Currency('USD'));
    $m2 = new Money(200, new Currency('USD'));

    expect(\ShamarKellman\FlexCart\Money\Calculator::add($m1, $m2)->getAmount())->toBe('300')
        ->and(\ShamarKellman\FlexCart\Money\Calculator::subtract($m2, $m1)->getAmount())->toBe('100')
        ->and(\ShamarKellman\FlexCart\Money\Calculator::multiply($m1, 2)->getAmount())->toBe('200')
        ->and(\ShamarKellman\FlexCart\Money\Calculator::divide($m2, 2)->getAmount())->toBe('100');
});

it('can get item by id', function () {
    $cartItem = FlexCart::addItem($this->product, 1);
    $found = FlexCart::getItem($cartItem->id);

    expect($found->id)->toBe($cartItem->id);
});

it('returns null when item not found', function () {
    expect(FlexCart::getItem(1000000))->toBeNull();
});

it('throws exception when adding non-buyable', function () {
    $nonBuyable = new class extends Model {};
    FlexCart::addItem($nonBuyable, 1);
})->throws(ProductNotBuyableException::class);

it('throws exception when adding quantity less than 1', function () {
    FlexCart::addItem($this->product, 0);
})->throws(InvalidQuantityException::class);

it('can check if cart is empty', function () {
    expect(FlexCart::isEmpty())->toBeTrue();
    FlexCart::addItem($this->product, 1);
    expect(FlexCart::isEmpty())->toBeFalse();
});

it('can update item with setQuantity and setOptions', function () {
    $item = FlexCart::addItem($this->product, 1);
    $item->setQuantity(5);
    $item->setOptions(['size' => 'L']);

    expect($item->getQuantity())->toBe(5)
        ->and($item->getOptions())->toBe(['size' => 'L']);
});

it('can get items using getItems', function () {
    FlexCart::addItem($this->product, 1);
    expect(FlexCart::getItems())->toHaveCount(1);
});

it('can use session storage has and forget', function () {
    config()->set('flex-cart.storage.driver', 'session');
    $sessionId = session()->getId();
    $storage = new \ShamarKellman\FlexCart\Storage\SessionStorage;

    expect($storage->has($sessionId))->toBeFalse();

    FlexCart::addItem($this->product, 1);

    expect($storage->has($sessionId))->toBeTrue();

    $storage->forget($sessionId);
    expect($storage->has($sessionId))->toBeFalse();
});

it('money cast throws exception for invalid value', function () {
    $cast = new \ShamarKellman\FlexCart\Money\MoneyCast;
    $model = new \ShamarKellman\FlexCart\Models\CartItem;
    $cast->set($model, 'unit_price', 'invalid', []);
})->throws(\InvalidArgumentException::class);

it('can add item with options', function () {
    $item = FlexCart::addItem($this->product, 1, ['color' => 'red', 'size' => 'M']);

    expect($item->getOptions())->toBe(['color' => 'red', 'size' => 'M']);
});

it('increases quantity when adding same item with same options', function () {
    FlexCart::addItem($this->product, 2, ['color' => 'blue']);
    FlexCart::addItem($this->product, 3, ['color' => 'blue']);

    expect(FlexCart::count())->toBe(5);
});

it('creates new item when adding same item with different options', function () {
    FlexCart::addItem($this->product, 1, ['color' => 'blue']);
    FlexCart::addItem($this->product, 1, ['color' => 'red']);

    expect(FlexCart::count())->toBe(2)
        ->and(FlexCart::items())->toHaveCount(2);
});

it('removes item when updating quantity to zero', function () {
    $item = FlexCart::addItem($this->product, 2);
    FlexCart::updateItem($item->id, 0);

    expect(FlexCart::count())->toBe(0);
});

it('dispatches cart cleared event', function () {
    \Illuminate\Support\Facades\Event::fake();
    FlexCart::addItem($this->product, 1);

    FlexCart::clear();

    \Illuminate\Support\Facades\Event::assertDispatched(\ShamarKellman\FlexCart\Events\CartCleared::class);
});

it('validates shipping cost currency', function () {
    FlexCart::addItem($this->product, 1);

    $eurCost = new Money(500, new Currency('EUR'));

    expect(fn () => FlexCart::setShippingCost($eurCost))
        ->toThrow(\ShamarKellman\FlexCart\Exceptions\CurrencyMismatchException::class);
});

it('loads cart with existing data from storage', function () {
    $storage = Mockery::mock(\ShamarKellman\FlexCart\Contracts\CartStorageInterface::class);
    $storage->shouldReceive('get')->andReturn([
        'cart' => ['id' => 1, 'shipping_cost' => 500],
        'items' => [
            [
                'id' => 1,
                'buyable_id' => $this->product->id,
                'buyable_type' => Product::class,
                'quantity' => 2,
                'unit_price' => 1000,
                'total_price' => 2000,
                'tax_amount' => 0,
                'options' => [],
            ],
        ],
    ]);

    $cart = new \ShamarKellman\FlexCart\FlexCart($storage);

    expect($cart->items())->toHaveCount(1)
        ->and($cart->count())->toBe(2)
        ->and($cart->shippingCost()->getAmount())->toBe('500');
});

it('loads cart with shipping detail from storage', function () {
    $storage = Mockery::mock(\ShamarKellman\FlexCart\Contracts\CartStorageInterface::class);
    $storage->shouldReceive('get')->andReturn([
        'cart' => [],
        'items' => [],
        'shipping' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ],
    ]);

    $cart = new \ShamarKellman\FlexCart\FlexCart($storage);
    $detail = $cart->getShippingDetail();

    expect($detail->first_name)->toBe('Jane')
        ->and($detail->last_name)->toBe('Doe');
});
