<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Money\Money;
use ShamarKellman\FlexCart\Money\MoneyCast;

/**
 * @property int $id
 * @property string $session_id
 * @property string $currency
 * @property Money $subtotal
 * @property Money $tax_amount
 * @property Money $shipping_cost
 * @property Money $total
 * @property array<string, mixed> $metadata
 * @property Collection<int, CartItem> $items
 * @property ShippingDetail|null $shippingDetail
 * @property User|null $user
 */
class Cart extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'currency',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total',
        'metadata',
        'applied_coupons',
    ];

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return HasOne<ShippingDetail, $this>
     */
    public function shippingDetail(): HasOne
    {
        return $this->hasOne(ShippingDetail::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<User> $model */
        $model = config()->string('auth.providers.users.model');

        return $this->belongsTo($model);
    }

    /**
     * @return array<string, string|class-string<MoneyCast>>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => MoneyCast::class,
            'tax_amount' => MoneyCast::class,
            'shipping_cost' => MoneyCast::class,
            'total' => MoneyCast::class,
            'metadata' => 'array',
            'applied_coupons' => 'array',
        ];
    }
}
