<?php

declare(strict_types=1);

use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Coupon\CouponCalculator;
use ShamarKellman\FlexCart\Models\CartItem;
use ShamarKellman\FlexCart\Models\Coupon;
use ShamarKellman\FlexCart\Tests\Models\Product;

function createCartItem(Product $product, int $quantity): CartItem
{
    $unitPrice = $product->getPrice();
    $total = $unitPrice->multiply((string) $quantity);

    $item = new CartItem([
        'buyable_id' => $product->id,
        'buyable_type' => get_class($product),
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total_price' => $total,
    ]);

    $item->setRelation('buyable', $product);

    return $item;
}

beforeEach(function () {
    $this->calculator = new CouponCalculator;
    $this->currency = new Currency('USD');

    $this->product1 = Product::create(['name' => 'Product 1', 'price' => 1000]);
    $this->product2 = Product::create(['name' => 'Product 2', 'price' => 2000]);
    $this->product3 = Product::create(['name' => 'Product 3', 'price' => 3000]);

    $this->items = collect([
        createCartItem($this->product1, 1),
        createCartItem($this->product2, 1),
        createCartItem($this->product3, 1),
    ]);

    $this->subtotal = new Money(6000, $this->currency);
});

describe('CouponCalculator', function () {
    describe('calculateDiscount', function () {
        it('calculates fixed amount discount', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $discount = $this->calculator->calculateDiscount($coupon, $this->subtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(500);
        });

        it('calculates percentage discount', function () {
            $coupon = Coupon::factory()->percentage(20)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $discount = $this->calculator->calculateDiscount($coupon, $this->subtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(1200);
        });

        it('calculates shipping discount', function () {
            $coupon = Coupon::factory()->shipping(10)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $discount = $this->calculator->calculateDiscount($coupon, $this->subtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(1000);
        });

        it('returns zero for invalid coupon', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->expired()->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $discount = $this->calculator->calculateDiscount($coupon, $this->subtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(0);
        });

        it('returns zero for coupon below minimum amount', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->withMinimumAmount(100)->create([
                'applies_to' => null,
                'excludes' => null,
            ]);
            $smallSubtotal = new Money(500, $this->currency);

            $discount = $this->calculator->calculateDiscount($coupon, $smallSubtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(0);
        });

        it('caps fixed discount at subtotal', function () {
            $coupon = Coupon::factory()->fixedAmount(100)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $discount = $this->calculator->calculateDiscount($coupon, $this->subtotal, $this->items);

            expect((int) $discount->getAmount())->toBe(6000);
        });
    });

    describe('validateCoupon', function () {
        it('validates active and valid coupon', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeTrue();
        });

        it('rejects expired coupon', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->expired()->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeFalse();
        });

        it('rejects inactive coupon', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->inactive()->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeFalse();
        });

        it('rejects coupon below minimum amount', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->withMinimumAmount(100)->create([
                'applies_to' => null,
                'excludes' => null,
            ]);
            $smallSubtotal = new Money(500, $this->currency);

            $result = $this->calculator->validateCoupon($coupon, $smallSubtotal, $this->items);

            expect($result)->toBeFalse();
        });

        it('rejects coupon with no applicable items', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->appliesTo([999])->create([
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeFalse();
        });

        it('rejects coupon at usage limit', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->withUsageLimit(1)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);
            $coupon->incrementUsage();

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeFalse();
        });

        it('rejects coupon that has not started yet', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->startsInDays(5)->create([
                'applies_to' => null,
                'excludes' => null,
                'minimum_amount' => 0,
            ]);

            $result = $this->calculator->validateCoupon($coupon, $this->subtotal, $this->items);

            expect($result)->toBeFalse();
        });
    });

    describe('getApplicableItems', function () {
        it('returns all items when no applies_to or excludes', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->create([
                'applies_to' => null,
                'excludes' => null,
            ]);

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(3);
        });

        it('filters items by applies_to product ids', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->appliesTo([$this->product1->id])->create([
                'excludes' => null,
            ]);

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(1)
                ->and($result->first()->buyable_id)->toBe($this->product1->id);
        });

        it('filters items by applies_to product types', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->appliesTo([Product::class])->create([
                'excludes' => null,
            ]);

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(3);
        });

        it('excludes items by excludes product ids', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->excludes([$this->product2->id, $this->product3->id])->create([
                'applies_to' => null,
            ]);

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(1)
                ->and($result->first()->buyable_id)->toBe($this->product1->id);
        });

        it('excludes items by excludes product types', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->excludes([Product::class])->create([
                'applies_to' => null,
            ]);

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(0);
        });

        it('prefers applies_to over excludes', function () {
            $coupon = Coupon::factory()->fixedAmount(5)
                ->appliesTo([$this->product1->id])
                ->excludes([$this->product1->id])
                ->create();

            $result = $this->calculator->getApplicableItems($coupon, $this->items);

            expect($result)->toHaveCount(1)
                ->and($result->first()->buyable_id)->toBe($this->product1->id);
        });
    });

    describe('calculateApplicableSubtotal', function () {
        it('calculates subtotal for all items when no filter', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->create([
                'applies_to' => null,
                'excludes' => null,
            ]);

            $result = $this->calculator->calculateApplicableSubtotal($coupon, $this->items);

            expect((int) $result->getAmount())->toBe(6000);
        });

        it('calculates subtotal for applicable items only', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->appliesTo([$this->product1->id])->create([
                'excludes' => null,
            ]);

            $result = $this->calculator->calculateApplicableSubtotal($coupon, $this->items);

            expect((int) $result->getAmount())->toBe(1000);
        });

        it('returns zero when no applicable items', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->appliesTo([999])->create([
                'excludes' => null,
            ]);

            $result = $this->calculator->calculateApplicableSubtotal($coupon, $this->items);

            expect((int) $result->getAmount())->toBe(0);
        });

        it('calculates subtotal for excluded items', function () {
            $coupon = Coupon::factory()->fixedAmount(5)->excludes([$this->product1->id])->create([
                'applies_to' => null,
            ]);

            $result = $this->calculator->calculateApplicableSubtotal($coupon, $this->items);

            expect((int) $result->getAmount())->toBe(5000);
        });
    });
});
