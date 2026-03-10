<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Money\Currency;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CouponInterface;
use ShamarKellman\FlexCart\Database\Factories\CouponFactory;
use ShamarKellman\FlexCart\Money\Calculator;
use ShamarKellman\FlexCart\Money\MoneyCast;

/**
 * @property int $id
 * @property string $code
 * @property string $type
 * @property Money|int $value
 * @property string $description
 * @property ?Money $minimum_amount
 * @property ?int $usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property bool $is_stackable
 * @property ?\DateTime $starts_at
 * @property ?\DateTime $expires_at
 * @property ?array $applies_to
 * @property ?array $excludes
 * @property array<string, mixed> $metadata
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Collection<int, CouponUsage> $usage
 */
#[UseFactory(CouponFactory::class)]
class Coupon extends Model implements CouponInterface
{
    use HasFactory;

    public const string TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const string TYPE_PERCENTAGE = 'percentage';

    public const string TYPE_SHIPPING = 'shipping';

    /**
     * @var list<string>
     *
     * @pest-mutate-ignore
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'description',
        'minimum_amount',
        'usage_limit',
        'used_count',
        'is_active',
        'is_stackable',
        'starts_at',
        'expires_at',
        'applies_to',
        'excludes',
        'metadata',
    ];

    /**
     * @return array<string, string|class-string<MoneyCast>>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'value' => 'integer',
            'minimum_amount' => MoneyCast::class,
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'applies_to' => 'array',
            'excludes' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usage(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): Money|int
    {
        return $this->value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getUsageLimit(): ?int
    {
        return $this->usage_limit;
    }

    public function getUsedCount(): int
    {
        return $this->used_count;
    }

    public function getMinimumAmount(): ?Money
    {
        return $this->minimum_amount;
    }

    public function isStackable(): bool
    {
        return $this->is_stackable;
    }

    public function getAppliesTo(): ?array
    {
        return $this->applies_to;
    }

    public function getExcludes(): ?array
    {
        return $this->excludes;
    }

    public function canBeApplied(Money $subtotal, Collection $items): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->minimum_amount && $subtotal->lessThan($this->minimum_amount)) {
            return false;
        }

        if ($this->applies_to || $this->excludes) {
            return $this->hasApplicableItems($items);
        }

        return true;
    }

    public function calculateDiscount(Money $subtotal, Collection $items): Money
    {
        if (! $this->canBeApplied($subtotal, $items)) {
            return new Money(0, $subtotal->getCurrency());
        }

        $applicableSubtotal = $this->calculateApplicableSubtotal($items, $subtotal->getCurrency());

        return match ($this->type) {
            self::TYPE_FIXED_AMOUNT => $this->calculateFixedAmountDiscount($applicableSubtotal),
            self::TYPE_PERCENTAGE => $this->calculatePercentageDiscount($applicableSubtotal),
            self::TYPE_SHIPPING => $this->calculateShippingDiscount($applicableSubtotal),
            default => new Money(0, $subtotal->getCurrency()),
        };
    }

    protected function calculateApplicableSubtotal(Collection $items, Currency $currency): Money
    {
        $applicableSubtotal = new Money(0, $currency);

        foreach ($items as $item) {
            if ($this->isItemApplicable($item)) {
                $applicableSubtotal = $applicableSubtotal->add($item->total_price);
            }
        }

        return $applicableSubtotal;
    }

    protected function isItemApplicable($item): bool
    {
        $buyable = $item->getBuyable();

        if (! empty($this->applies_to)) {
            return in_array((int) $buyable->getKey(), $this->applies_to) ||
                   in_array(get_class($buyable), $this->applies_to);
        }

        if (! empty($this->excludes)) {
            return ! (in_array((int) $buyable->getKey(), $this->excludes) ||
                      in_array(get_class($buyable), $this->excludes));
        }

        return true;
    }

    protected function hasApplicableItems(Collection $items): bool
    {
        return $items->contains(fn ($item) => $this->isItemApplicable($item));
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
        $this->refresh();
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
        ]);
    }

    protected function calculateFixedAmountDiscount(Money $subtotal): Money
    {
        $discount = new Money($this->value, $subtotal->getCurrency());

        return $discount->greaterThan($subtotal)
            ? $subtotal
            : $discount;
    }

    protected function calculatePercentageDiscount(Money $subtotal): Money
    {
        return Calculator::percentage($subtotal, $this->value);
    }

    protected function calculateShippingDiscount(Money $subtotal): Money
    {
        return new Money($this->value, $subtotal->getCurrency());
    }
}
