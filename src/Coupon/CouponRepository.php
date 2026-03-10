<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Coupon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ShamarKellman\FlexCart\Contracts\CouponInterface;
use ShamarKellman\FlexCart\Contracts\CouponRepositoryInterface;
use ShamarKellman\FlexCart\Models\Coupon;

class CouponRepository implements CouponRepositoryInterface
{
    public function findByCode(string $code): ?CouponInterface
    {
        return Coupon::query()
            ->where('code', $code)
            ->first();
    }

    public function findValidByCode(string $code): ?CouponInterface
    {
        $coupon = $this->findByCode($code);

        if ($coupon && $coupon->isValid()) {
            return $coupon;
        }

        return null;
    }

    public function findActive(): Collection
    {
        /**
         * @var Collection<int, CouponInterface|Coupon|Model> $coupons
         */
        $coupons = Coupon::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        return $coupons;
    }

    public function save(CouponInterface|Coupon|Model $coupon): bool
    {
        return $coupon->save();
    }

    public function delete(CouponInterface|Coupon|Model $coupon): bool
    {
        return (bool) $coupon->delete();
    }

    public function incrementUsage(string $code): bool
    {
        $coupon = $this->findByCode($code);

        if (! $coupon) {
            return false;
        }

        $coupon->incrementUsage();

        return true;
    }

    public function findExpired(): Collection
    {
        /**
         * @var Collection<int, CouponInterface|Coupon|Model> $coupons
         */
        $coupons = Coupon::query()
            ->where('expires_at', '<', now())
            ->where('is_active', true)
            ->get();

        return $coupons;
    }

    public function findExpiringSoon(int $days): Collection
    {
        /**
         * @var Collection<int, CouponInterface|Coupon|Model> $coupons
         */
        $coupons = Coupon::query()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('is_active', true)
            ->get();

        return $coupons;
    }
}
