<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Exceptions;

use Exception;

class CartException extends Exception
{
    protected ?string $cartId = null;

    public function setCartId(?string $cartId): self
    {
        $this->cartId = $cartId;

        return $this;
    }

    public function getCartId(): ?string
    {
        return $this->cartId;
    }
}
