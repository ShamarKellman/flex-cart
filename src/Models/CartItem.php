<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Money\Money;
use ShamarKellman\FlexCart\Contracts\CartItemInterface;
use ShamarKellman\FlexCart\Money\MoneyCast;

/**
 * @template TModel of Model
 *
 * @property int $id
 * @property Cart $cart
 * @property TModel $buyable
 * @property int $quantity
 * @property Money $unit_price
 * @property Money $total_price
 * @property Money $tax_amount
 * @property array<string, mixed> $options
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $cart_id
 * @property int $buyable_id
 * @property string $buyable_type
 */
class CartItem extends Model implements CartItemInterface
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'buyable_id',
        'buyable_type',
        'quantity',
        'unit_price',
        'total_price',
        'tax_amount',
        'options',
    ];

    protected $casts = [
        'unit_price' => MoneyCast::class,
        'total_price' => MoneyCast::class,
        'tax_amount' => MoneyCast::class,
        'options' => 'array',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function buyable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unit_price;
    }

    public function getTotal(): Money
    {
        return $this->total_price;
    }

    public function getTaxAmount(): Money
    {
        return $this->tax_amount;
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return TModel
     */
    public function getBuyable(): Model
    {
        return $this->buyable;
    }
}
