<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ShamarKellman\FlexCart\FlexCart
 *
 * @mixin \ShamarKellman\FlexCart\FlexCart
 */
class FlexCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ShamarKellman\FlexCart\FlexCart::class;
    }
}
