<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Cart $cart
 * @property-read string $shipping_method
 * @property-read string $notes
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $company
 * @property-read string $address_line_1
 * @property-read string $address_line_2
 * @property-read string $city
 * @property-read string $state
 * @property-read string $postal_code
 * @property-read string $country
 * @property-read string $phone
 * @property-read string $email
 * @property-read int $cart_id
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read string $deleted_at
 */
class ShippingDetail extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'shipping_method',
        'notes',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
