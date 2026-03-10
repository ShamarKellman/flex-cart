<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Exceptions;

use Illuminate\Database\Eloquent\Model;

class ProductNotBuyableException extends CartException
{
    public static function fromModel(Model $model): self
    {
        $className = get_class($model);

        return new self("Model {$className} must implement BuyableInterface to be added to cart.");
    }

    public static function missingPrice(mixed $model): self
    {
        $className = is_object($model) ? get_class($model) : $model;

        return new self("Buyable model {$className} must have a price or implement getPrice() method.");
    }

    public static function invalidPrice(mixed $price, string $model): self
    {
        $type = gettype($price);

        return new self("Invalid price type {$type} for model {$model}. Price must be a Money object or numeric value.");
    }
}
