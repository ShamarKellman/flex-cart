<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

use Illuminate\Support\Collection;
use Money\Money;

interface CouponInterface
{
    public function getCode(): string;

    public function getType(): string;

    public function getValue(): Money|int;

    public function getDescription(): string;

    public function isValid(): bool;

    public function isExpired(): bool;

    public function getUsageLimit(): ?int;

    public function getUsedCount(): int;

    public function getMinimumAmount(): ?Money;

    public function isStackable(): bool;

    public function getAppliesTo(): ?array;

    public function getExcludes(): ?array;

    /**
     * @param  Collection<int, CouponInterface>  $items
     */
    public function canBeApplied(Money $subtotal, Collection $items): bool;

    /**
     * @param  Collection<int, CouponInterface>  $items
     */
    public function calculateDiscount(Money $subtotal, Collection $items): Money;

    public function incrementUsage(): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
