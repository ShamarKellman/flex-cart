<?php

declare(strict_types=1);

use ShamarKellman\FlexCart\Coupon\CouponRepository;
use ShamarKellman\FlexCart\Models\Coupon;

beforeEach(function () {
    $this->repository = new CouponRepository;

    $this->activeCoupon = Coupon::factory()->active()->create([
        'code' => 'ACTIVE10',
        'type' => 'percentage',
        'value' => 10,
    ]);

    $this->expiredCoupon = Coupon::factory()->expired()->create([
        'code' => 'EXPIRED20',
        'type' => 'percentage',
        'value' => 20,
    ]);

    $this->inactiveCoupon = Coupon::factory()->inactive()->create([
        'code' => 'INACTIVE30',
        'type' => 'percentage',
        'value' => 30,
    ]);

    $this->validCoupon = Coupon::factory()->active()->expiresInDays(10)->create([
        'code' => 'VALID15',
        'type' => 'percentage',
        'value' => 15,
    ]);
});

describe('CouponRepository', function () {
    describe('findByCode', function () {
        it('finds coupon by code', function () {
            $result = $this->repository->findByCode('ACTIVE10');

            expect($result)->not->toBeNull()
                ->and($result?->getCode())->toBe('ACTIVE10');
        });

        it('returns null for non-existent code', function () {
            $result = $this->repository->findByCode('NONEXISTENT');

            expect($result)->toBeNull();
        });

        it('is case sensitive', function () {
            $result = $this->repository->findByCode('active10');

            expect($result)->toBeNull();
        });
    });

    describe('findValidByCode', function () {
        it('finds valid coupon by code', function () {
            $result = $this->repository->findValidByCode('VALID15');

            expect($result)->not->toBeNull()
                ->and($result?->getCode())->toBe('VALID15');
        });

        it('returns null for expired coupon', function () {
            $result = $this->repository->findValidByCode('EXPIRED20');

            expect($result)->toBeNull();
        });

        it('returns null for inactive coupon', function () {
            $result = $this->repository->findValidByCode('INACTIVE30');

            expect($result)->toBeNull();
        });

        it('returns null for non-existent code', function () {
            $result = $this->repository->findValidByCode('NONEXISTENT');

            expect($result)->toBeNull();
        });
    });

    describe('findActive', function () {
        it('finds all active coupons', function () {
            $result = $this->repository->findActive();

            expect($result)->toHaveCount(2)
                ->and($result->pluck('code')->toArray())->toContain('ACTIVE10', 'VALID15');
        });

        it('excludes inactive coupons', function () {
            $result = $this->repository->findActive();

            expect($result->pluck('code')->toArray())->not->toContain('INACTIVE30');
        });

        it('excludes expired coupons', function () {
            $result = $this->repository->findActive();

            expect($result->pluck('code')->toArray())->not->toContain('EXPIRED20');
        });

        it('includes coupons with no expiration', function () {
            $result = $this->repository->findActive();

            expect($result->pluck('code')->toArray())->toContain('ACTIVE10');
        });
    });

    describe('save', function () {
        it('saves a new coupon', function () {
            $coupon = Coupon::factory()->create([
                'code' => 'NEWCOUPON',
            ]);

            $result = $this->repository->save($coupon);

            expect($result)->toBeTrue()
                ->and($coupon->exists)->toBeTrue();
        });

        it('saves changes to existing coupon', function () {
            $this->activeCoupon->description = 'Updated description';

            $result = $this->repository->save($this->activeCoupon);

            expect($result)->toBeTrue();
        });
    });

    describe('delete', function () {
        it('deletes a coupon', function () {
            $coupon = Coupon::factory()->create([
                'code' => 'DELETEME',
            ]);

            $result = $this->repository->delete($coupon);

            expect($result)->toBeTrue()
                ->and(Coupon::where('code', 'DELETEME')->first())->toBeNull();
        });

        it('returns false when deleting non-existent model', function () {
            $coupon = new Coupon(['id' => 99999]);

            $result = $this->repository->delete($coupon);

            expect($result)->toBeFalse();
        });
    });

    describe('incrementUsage', function () {
        it('increments usage count for existing coupon', function () {
            $coupon = Coupon::factory()->create([
                'code' => 'USAGETEST',
                'used_count' => 5,
            ]);

            $result = $this->repository->incrementUsage('USAGETEST');

            expect($result)->toBeTrue()
                ->and($coupon->fresh()->used_count)->toBe(6);
        });

        it('returns false for non-existent coupon', function () {
            $result = $this->repository->incrementUsage('NONEXISTENT');

            expect($result)->toBeFalse();
        });
    });

    describe('findExpired', function () {
        it('finds expired coupons', function () {
            $result = $this->repository->findExpired();

            expect($result)->toHaveCount(1)
                ->and($result->first()?->getCode())->toBe('EXPIRED20');
        });

        it('excludes active coupons', function () {
            $result = $this->repository->findExpired();

            expect($result->pluck('code')->toArray())->not->toContain('ACTIVE10', 'VALID15');
        });

        it('excludes inactive coupons even if expired', function () {
            $result = $this->repository->findExpired();

            expect($result->pluck('code')->toArray())->not->toContain('INACTIVE30');
        });
    });

    describe('findExpiringSoon', function () {
        it('finds coupons expiring within specified days', function () {
            $coupon = Coupon::factory()->active()->expiresInDays(5)->create([
                'code' => 'EXPIRINGSOON',
            ]);

            $result = $this->repository->findExpiringSoon(7);

            expect($result)->toHaveCount(1)
                ->and($result->first()?->getCode())->toBe('EXPIRINGSOON');
        });

        it('excludes coupons not expiring soon', function () {
            $coupon = Coupon::factory()->active()->expiresInDays(30)->create([
                'code' => 'NOTEXPIRINGSOON',
            ]);

            $result = $this->repository->findExpiringSoon(7);

            expect($result->pluck('code')->toArray())->not->toContain('NOTEXPIRINGSOON');
        });

        it('excludes expired coupons', function () {
            $result = $this->repository->findExpiringSoon(30);

            expect($result->pluck('code')->toArray())->not->toContain('EXPIRED20');
        });

        it('excludes inactive coupons', function () {
            $result = $this->repository->findExpiringSoon(30);

            expect($result->pluck('code')->toArray())->not->toContain('INACTIVE30');
        });

        it('includes coupons expiring exactly on the boundary', function () {
            $coupon = Coupon::factory()->active()->expiresInDays(7)->create([
                'code' => 'EXACTBOUNDARY',
            ]);

            $result = $this->repository->findExpiringSoon(7);

            expect($result->pluck('code')->toArray())->toContain('EXACTBOUNDARY');
        });
    });
});
