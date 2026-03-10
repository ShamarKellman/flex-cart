<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ShamarKellman\FlexCart\Models\ShippingDetail;

class ShippingAddressSet
{
    use Dispatchable;

    public function __construct(
        protected ?ShippingDetail $oldAddress,
        protected ShippingDetail $newAddress
    ) {}

    public function getOldAddress(): ?ShippingDetail
    {
        return $this->oldAddress;
    }

    public function getNewAddress(): ShippingDetail
    {
        return $this->newAddress;
    }

    public function isFirstAddress(): bool
    {
        return $this->oldAddress === null;
    }

    public function hasChanged(): bool
    {
        if ($this->isFirstAddress()) {
            return true;
        }

        return $this->oldAddress->toArray() !== $this->newAddress->toArray();
    }

    public function getCountry(): string
    {
        return $this->newAddress->country;
    }

    public function getPostalCode(): string
    {
        return $this->newAddress->postal_code;
    }

    public function isInternational(?string $homeCountry = null): bool
    {
        if (! $homeCountry) {
            return false;
        }

        return $this->newAddress->country !== $homeCountry;
    }
}
