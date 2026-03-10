<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Traits;

use Money\Money;
use ShamarKellman\FlexCart\Events\ShippingAddressSet;
use ShamarKellman\FlexCart\Events\ShippingCostSet;
use ShamarKellman\FlexCart\Models\ShippingDetail;

trait HasShipping
{
    /**
     * Get the shipping cost
     */
    public function shippingCost(): Money
    {
        return $this->shippingCost;
    }

    /**
     * Set the shipping address
     *
     * @param  array<string, mixed>  $address
     */
    public function setShippingAddress(array $address): void
    {
        $oldShippingDetail = $this->shippingDetail;
        $this->shippingDetail = new ShippingDetail($address);
        $this->save();

        // Dispatch shipping address set event
        event(new ShippingAddressSet($oldShippingDetail, $this->shippingDetail));
    }

    /**
     * Set the shipping cost
     */
    public function setShippingCost(Money $cost): void
    {
        $this->validateCurrency($cost, 'shipping cost');
        $oldShippingCost = $this->shippingCost;
        $this->shippingCost = $cost;
        $this->save();

        // Dispatch shipping cost set event
        event(new ShippingCostSet($oldShippingCost, $cost));
    }

    /**
     * Get the shipping detail
     */
    public function getShippingDetail(): ?ShippingDetail
    {
        return $this->shippingDetail;
    }
}
