<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Events\CouponApplied;
use ShamarKellman\FlexCart\Events\CouponExpired;
use ShamarKellman\FlexCart\Events\CouponRemoved;
use ShamarKellman\FlexCart\Facades\FlexCart;
use ShamarKellman\FlexCart\Models\Coupon;
use ShamarKellman\FlexCart\Tests\Models\Product;

beforeEach(function () {
    $this->product = Product::create([
        'name' => 'Test Product',
        'price' => 1000,
    ]);

    $this->cart = FlexCart::addItem($this->product);
});

afterEach(function () {
    FlexCart::clear();
});

it('can add coupon to cart', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    $result = FlexCart::addCoupon($coupon);

    expect($result)->toBeTrue()
        ->and(FlexCart::hasCoupon($coupon->code))->toBeTrue()
        ->and(FlexCart::getCoupon($coupon->code))->not->toBeNull()
        ->and(FlexCart::getCoupons())->toHaveCount(1);
});

it('cannot add duplicate coupon', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon);
    $result = FlexCart::addCoupon($coupon);

    expect($result)->toBeFalse()
        ->and(FlexCart::getCoupons())->toHaveCount(1);
});

it('cannot add non stackable coupon with existing coupon', function () {
    $coupon1 = Coupon::factory()->fixedAmount(50)->notStackable()->create();
    $coupon2 = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon1);
    $result = FlexCart::addCoupon($coupon2);

    expect($result)->toBeFalse()
        ->and(FlexCart::getCoupons())->toHaveCount(1);
});

it('can add stackable coupons', function () {
    $coupon1 = Coupon::factory()->fixedAmount(50)->stackable()->create();
    $coupon2 = Coupon::factory()->percentage(10)->stackable()->create();

    FlexCart::addCoupon($coupon1);
    FlexCart::addCoupon($coupon2);

    expect(FlexCart::getCoupons())->toHaveCount(2)
        ->and(FlexCart::hasCoupon($coupon1->code))->toBeTrue()
        ->and(FlexCart::hasCoupon($coupon2->code))->toBeTrue();
});

it('can remove coupon from cart', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon);
    $result = FlexCart::removeCoupon($coupon->code);

    expect($result)->toBeTrue()
        ->and(FlexCart::hasCoupon($coupon->code))->toBeFalse()
        ->and(FlexCart::getCoupons())->toHaveCount(0);
});

it('cannot remove nonexistent coupon', function () {
    $result = FlexCart::removeCoupon('NONEXISTENT');

    expect($result)->toBeFalse();
});

it('clear coupons removes all coupons', function () {
    $coupon1 = Coupon::factory()->fixedAmount(50)->stackable()->create();
    $coupon2 = Coupon::factory()->percentage(10)->stackable()->create();

    FlexCart::addCoupon($coupon1);
    FlexCart::addCoupon($coupon2);
    FlexCart::clearCoupons();

    expect(FlexCart::getCoupons())->toHaveCount(0)
        ->and(FlexCart::hasCoupon($coupon1->code))->toBeFalse()
        ->and(FlexCart::hasCoupon($coupon2->code))->toBeFalse();
});

it('calculates fixed amount coupon discount', function () {
    $coupon = Coupon::factory()->fixedAmount(2)->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(200);
});

it('calculates percentage coupon discount', function () {
    $coupon = Coupon::factory()->expiresInDays(5)->percentage(25)->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(250);
});

it('calculates shipping coupon discount', function () {
    $coupon = Coupon::factory()->shipping(15)->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::shippingDiscount();

    expect((int) $discount->getAmount())->toBe(1500);
});

it('respects minimum amount requirement', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->withMinimumAmount(50)->create();
    $smallProduct = Product::create(['name' => 'Small Product', 'price' => 300]);

    FlexCart::addItem($smallProduct);
    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(0);
});

it('does not apply expired coupon', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->expired()->create();

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(0)
        ->and(FlexCart::hasCoupon($coupon->code))->toBeFalse();
});

it('does not apply inactive coupon', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->inactive()->create();

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(0);
});

it('calculates total with coupons', function () {
    $coupon = Coupon::factory()->percentage(20)->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);
    FlexCart::setShippingCost(new Money(500, new Currency('USD')));

    FlexCart::addCoupon($coupon);
    FlexCart::subtotal();
    $subtotalWithCoupons = FlexCart::subtotalWithCoupons();
    $total = FlexCart::total();

    expect((int) $subtotalWithCoupons->getAmount())->toBe(800)
        ->and((int) $total->getAmount())->toBeGreaterThan((int) $subtotalWithCoupons->getAmount());
});

it('applies coupons to specific products only', function () {
    $product1 = Product::create(['name' => 'Product 1', 'price' => 1000]);
    $product2 = Product::create(['name' => 'Product 2', 'price' => 1000]);
    $coupon = Coupon::factory()->percentage(50)->appliesTo([$product1->id])->create([
        'minimum_amount' => 0,
    ]);

    FlexCart::addItem($product1);
    FlexCart::addItem($product2);
    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(500);
});

it('excludes specific products from coupon', function () {
    $product1 = Product::create(['name' => 'Product 1', 'price' => 1000]);
    $product2 = Product::create(['name' => 'Product 2', 'price' => 1000]);
    $coupon = Coupon::factory()->percentage(50)->excludes([$product2->id, $this->product->id])->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);

    FlexCart::addItem($product1);
    FlexCart::addItem($product2);
    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(500);
});

it('respects usage limit', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->withUsageLimit(1)->create();

    $coupon->incrementUsage();

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(0);
});

it('limits discount to subtotal amount', function () {
    $coupon = Coupon::factory()->fixedAmount(2000)->create([
        'minimum_amount' => 0,
        'applies_to' => null,
    ]);

    FlexCart::addCoupon($coupon);
    $discount = FlexCart::couponDiscount();

    expect((int) $discount->getAmount())->toBe(1000);
});

it('dispatches coupon applied event', function () {
    Event::fake();
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon);

    Event::assertDispatched(CouponApplied::class, function ($event) use ($coupon) {
        return $event->getCoupon()->getCode() === $coupon->getCode();
    });
});

it('dispatches coupon removed event', function () {
    Event::fake();
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon);
    FlexCart::removeCoupon($coupon->code);

    Event::assertDispatched(CouponRemoved::class, function ($event) use ($coupon) {
        return $event->getCoupon()->getCode() === $coupon->getCode();
    });
});

it('dispatches coupon expired event', function () {
    Event::fake();
    $coupon = Coupon::factory()->fixedAmount(100)->expired()->create();

    FlexCart::addCoupon($coupon);

    Event::assertDispatched(CouponExpired::class, function ($event) use ($coupon) {
        return $event->getCoupon()->getCode() === $coupon->getCode();
    });
});

it('clear cart removes coupons', function () {
    $coupon = Coupon::factory()->fixedAmount(100)->create();

    FlexCart::addCoupon($coupon);
    expect(FlexCart::hasCoupon($coupon->code))->toBeTrue();

    FlexCart::clear();
    expect(FlexCart::hasCoupon($coupon->code))->toBeFalse();
});
