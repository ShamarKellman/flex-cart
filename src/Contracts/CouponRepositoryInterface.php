<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ShamarKellman\FlexCart\Models\Coupon;

interface CouponRepositoryInterface
{
    public function findByCode(string $code): ?CouponInterface;

    public function findValidByCode(string $code): ?CouponInterface;

    /**
     * @return Collection<int, CouponInterface|Coupon|Model>
     */
    public function findActive(): Collection;

    public function save(CouponInterface|Coupon|Model $coupon): bool;

    public function delete(CouponInterface|Coupon|Model $coupon): bool;

    public function incrementUsage(string $code): bool;

    /**
     * @return Collection<int, CouponInterface|Coupon|Model>
     */
    public function findExpired(): Collection;

    /**
     * @return Collection<int, CouponInterface|Coupon|Model>
     */
    public function findExpiringSoon(int $days): Collection;
}
