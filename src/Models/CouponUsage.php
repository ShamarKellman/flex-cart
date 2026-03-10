<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Money\Money;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int $cart_id
 * @property ?int $user_id
 * @property Money $discount_amount
 * @property string $currency
 * @property array<string, mixed> $metadata
 * @property CarbonInterface $created_at
 * @property Coupon $coupon
 * @property Cart $cart
 * @property User|null $user
 */
class CouponUsage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'coupon_id',
        'cart_id',
        'user_id',
        'discount_amount',
        'currency',
        'metadata',
    ];

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
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
}
